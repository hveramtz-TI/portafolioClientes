import type { CatalogItemType, CatalogNode, CreatePersonalItemInput } from '../api';

/** Editable form values held by `CatalogItemDialog`. */
export interface CatalogDialogValues {
  name: string;
  title: string;
  description: string;
  value: string;
  tags: string[];
  /** Fields the user explicitly reverted to base (payload value becomes `null`). */
  reverted: string[];
}

/** Dialog-level errors mapped from a rejected save or supplied by the page. */
export interface FormErrors {
  validationErrors?: Record<string, string[]>;
  formError?: string;
}

/** Shown when a save fails for a reason the server did not describe field-by-field. */
export const GENERIC_SAVE_ERROR = 'The item could not be saved.';

export function toInputValue(value: number | null | undefined): string {
  return value === null || value === undefined ? '' : String(value);
}

export function toNumber(input: string): number | null {
  return input.trim() === '' ? null : Number(input);
}

export function sameTags(current: string[], initial: string[]): boolean {
  if (current.length !== initial.length) return false;
  const sortedInitial = [...initial].sort();
  return [...current].sort().every((tag, index) => tag === sortedInitial[index]);
}

/**
 * Builds the submit payload from the dialog values.
 *
 * Create mode sends every type-relevant field (plus `parent_fork_id`). Edit mode
 * sends only changed fields so untouched values keep their origin; a reverted
 * field is sent as explicit `null` to clear its override.
 */
export function buildCatalogInput(
  type: CatalogItemType,
  item: CatalogNode | null | undefined,
  values: CatalogDialogValues,
  parentForkId?: string | null,
): CreatePersonalItemInput {
  const isService = type === 'service';

  if (!item) {
    const input: CreatePersonalItemInput = isService
      ? { title: values.title, description: values.description, value: toNumber(values.value), tags: values.tags }
      : { name: values.name, description: values.description };
    if (parentForkId) input.parent_fork_id = parentForkId;
    return input;
  }

  const input: CreatePersonalItemInput = {};
  const reverted = values.reverted;

  if (isService) {
    if (reverted.includes('title')) input.title = null;
    else if (values.title !== (item.title ?? '')) input.title = values.title;
    if (reverted.includes('description')) input.description = null;
    else if (values.description !== (item.description ?? '')) input.description = values.description;
    if (reverted.includes('value')) input.value = null;
    else if (toNumber(values.value) !== (item.value ?? null)) input.value = toNumber(values.value);
    if (reverted.includes('tags')) input.tags = null;
    else if (!sameTags(values.tags, item.tags ?? [])) input.tags = values.tags;
  } else {
    if (reverted.includes('name')) input.name = null;
    else if (values.name !== (item.name ?? '')) input.name = values.name;
    if (reverted.includes('description')) input.description = null;
    else if (values.description !== (item.description ?? '')) input.description = values.description;
  }

  // A destination parent is only present when the user picked a new one, so an
  // unchanged selector leaves the parent untouched (omitted).
  if (parentForkId) input.parent_fork_id = parentForkId;

  return input;
}

/**
 * Maps a rejected save into dialog-level errors. A Laravel 422 payload carries
 * an `errors` map; anything else falls back to the generic message.
 */
export function toFormErrors(error: unknown): FormErrors {
  const candidate = error as { errors?: unknown } | null;
  if (candidate && typeof candidate.errors === 'object' && candidate.errors !== null) {
    return { validationErrors: candidate.errors as Record<string, string[]> };
  }
  return { formError: GENERIC_SAVE_ERROR };
}
