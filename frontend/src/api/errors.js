/**
 * Traduce un ApiError a un mensaje entendible para la persona usuaria.
 * Los mensajes del servidor ya vienen en español; aquí se ajustan los casos
 * en que conviene explicar qué hacer.
 */
export function describeError(error) {
  if (!error) return null
  switch (error.code) {
    case 'football_data_rate_limited':
      return {
        title: 'Límite de consultas a football-data alcanzado',
        message: `El servicio externo solo permite 10 consultas por minuto. Intenta de nuevo en ${error.retryAfter ?? 60} segundos. Lo que ya está guardado sigue disponible.`,
      }
    case 'football_data_unavailable':
    case 'football_data_bad_gateway':
    case 'football_data_invalid_token':
      return { title: 'football-data.org no responde', message: `${error.message} Los datos guardados siguen disponibles.` }
    case 'football_data_not_configured':
      return { title: 'Servicio externo sin configurar', message: error.message }
    case 'football_data_restricted':
      return { title: 'Liga no disponible', message: error.message }
    case 'import_not_allowed':
      return { title: 'Liga aún no importada', message: 'Tu rol solo puede consultar ligas ya importadas. Pide a un analista o administrador que la importe.' }
    case 'forbidden':
      return { title: 'Sin permiso', message: error.message }
    case 'too_many_requests':
      return { title: 'Demasiadas solicitudes', message: error.message }
    case 'import_in_progress':
      return { title: 'Importación en curso', message: error.message }
    case 'not_found':
    case 'football_data_not_found':
      return { title: 'No encontrado', message: error.message }
    case 'network_error':
      return { title: 'Sin conexión', message: error.message }
    default:
      return { title: 'Algo salió mal', message: error.message }
  }
}
