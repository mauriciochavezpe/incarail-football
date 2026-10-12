// football-data entrega posiciones en inglés y con distinto detalle según la liga.
const LABELS = {
  goalkeeper: 'Porteros',
  defence: 'Defensas', defender: 'Defensas', 'centre-back': 'Defensas', 'left-back': 'Defensas', 'right-back': 'Defensas',
  midfield: 'Mediocampistas', midfielder: 'Mediocampistas', 'central midfield': 'Mediocampistas', 'defensive midfield': 'Mediocampistas', 'attacking midfield': 'Mediocampistas', 'left midfield': 'Mediocampistas', 'right midfield': 'Mediocampistas',
  offence: 'Delanteros', attacker: 'Delanteros', 'centre-forward': 'Delanteros', 'left winger': 'Delanteros', 'right winger': 'Delanteros',
}
const ORDER = ['Porteros', 'Defensas', 'Mediocampistas', 'Delanteros']

export const positionLabel = (p) => (p ? LABELS[p.toLowerCase()] ?? p : 'Sin posición')
export const positionOrder = (label) => (ORDER.includes(label) ? ORDER.indexOf(label) : ORDER.length)
