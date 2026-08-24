/**
 * Formatea un RUT chileno con puntos y guion para display.
 * Ejemplo: "12345678-5" -> "12.345.678-5".
 */
export function formatRut(rut: string): string {
  const clean = rut.replace(/[^0-9Kk]/g, '').toUpperCase();

  if (clean.length < 2) {
    return rut;
  }

  const body = clean.slice(0, -1);
  const dv = clean.slice(-1);
  const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  return `${withDots}-${dv}`;
}
