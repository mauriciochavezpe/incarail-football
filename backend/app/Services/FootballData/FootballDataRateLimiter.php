<?php

namespace App\Services\FootballData;

use App\Exceptions\FootballData\RateLimitReachedException;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Limitador GLOBAL de peticiones salientes a football-data.org.
 *
 * - Ventana deslizante: guarda las marcas de tiempo de las peticiones del
 *   último minuto y solo deja pasar una nueva si hay cupo.
 * - Atómico: la lectura y escritura de la ventana se hace bajo un lock de
 *   caché, así dos peticiones simultáneas no pueden gastar el mismo cupo.
 * - Se sincroniza con football-data: si sus cabeceras dicen que no queda cupo
 *   (o responde 429), bloqueamos hasta que su contador se reinicie.
 *
 * No espera ni duerme: si no hay cupo, falla rápido con el tiempo de espera,
 * y quien llama decide (responder con datos locales o devolver 429).
 */
class FootballDataRateLimiter
{
    private const string WINDOW_KEY = 'football-data:rate-limit:window';

    private const string BLOCK_KEY = 'football-data:rate-limit:blocked-until';

    private const string LOCK_KEY = 'football-data:rate-limit:lock';

    public function __construct(
        private readonly Cache $cache,
        private readonly int $maxRequests,
        private readonly int $windowSeconds = 60,
    ) {}

    /**
     * Reserva $cost peticiones o lanza RateLimitReachedException.
     *
     * @throws RateLimitReachedException
     */
    public function acquire(int $cost = 1): void
    {
        $this->cache->lock(self::LOCK_KEY, 5)->block(5, function () use ($cost) {
            $now = microtime(true);

            $blockedUntil = (float) $this->cache->get(self::BLOCK_KEY, 0);
            if ($blockedUntil > $now) {
                throw new RateLimitReachedException((int) ceil($blockedUntil - $now));
            }

            $window = $this->currentWindow($now);

            if (count($window) + $cost > $this->maxRequests) {
                // El cupo se libera cuando la petición más antigua sale de la ventana.
                $index = count($window) + $cost - $this->maxRequests - 1;
                $freesAt = ($window[$index] ?? $now) + $this->windowSeconds;
                throw new RateLimitReachedException((int) ceil($freesAt - $now));
            }

            for ($i = 0; $i < $cost; $i++) {
                $window[] = $now;
            }

            $this->cache->put(self::WINDOW_KEY, $window, $this->windowSeconds);
        });
    }

    /** Bloquea toda salida durante $seconds (p. ej. tras un 429 de football-data). */
    public function blockFor(int $seconds): void
    {
        $seconds = max(1, $seconds);
        $this->cache->put(self::BLOCK_KEY, microtime(true) + $seconds, $seconds);
    }

    /**
     * Usa las cabeceras de football-data para no fiarnos solo de nuestra cuenta
     * (el token puede estar usándose desde otro lugar).
     */
    public function syncWithUpstream(?int $available, ?int $resetInSeconds): void
    {
        if ($available !== null && $available <= 0) {
            $this->blockFor($resetInSeconds ?? $this->windowSeconds);
        }
    }

    /** Peticiones que quedan en la ventana actual (para diagnóstico). */
    public function remaining(): int
    {
        return max(0, $this->maxRequests - count($this->currentWindow(microtime(true))));
    }

    /** @return list<float> */
    private function currentWindow(float $now): array
    {
        $window = $this->cache->get(self::WINDOW_KEY, []);

        return array_values(array_filter(
            is_array($window) ? $window : [],
            fn (float $t) => $t > $now - $this->windowSeconds,
        ));
    }
}
