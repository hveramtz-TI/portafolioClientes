"use client";

import { useEffect, useState } from "react";

/**
 * Debouncea un valor: devuelve la última versión del valor después de `delay` ms
 * sin cambios. Útil para inputs de búsqueda que disparan requests a la API:
 * así no consultás en cada tecla.
 *
 * @param value    Valor a debouncear
 * @param delay    Milisegundos de espera desde la última entrada
 *
 * Ejemplo:
 *   const [search, setSearch] = useState("");
 *   const debouncedSearch = useDebouncedValue(search, 300);
 *   useEffect(() => loadWith(debouncedSearch), [debouncedSearch]);
 */
export function useDebouncedValue<T>(value: T, delay: number): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debounced;
}
