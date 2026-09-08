import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Combina clases CSS condicionales de Tailwind de forma segura.
 * - Une múltiples clases usando clsx
 * - Resuelve conflictos automáticamente usando twMerge (prefiere la última clase definida)
 * - Evita el uso de !important; sigue la lógica natural de Tailwind CSS
 *
 * Ejemplos de uso:
 *  cn("base-class", "hover:bg-blue-500")
 *  cn("bg-white", { "bg-blue-500": isActive })
 *  cn("p-4 md:p-6 dark:bg-gray-800")
 *
 * @param inputs - Clases CSS condicionales (pueden ser strings, arrays u objetos condicionales)
 * @returns String con las clases combinadas y sin conflictos
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
