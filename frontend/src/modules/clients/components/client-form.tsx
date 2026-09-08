'use client';

import { useState, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { createClient, updateClient } from '@/modules/clients/api';
import type { Client } from '@/types';

interface ClientFormProps {
  client?: Client | null;
  onSuccess: () => void;
}

interface FormValues {
  name: string;
  surname: string;
  rut: string;
  email: string;
  phone: string;
  address: string;
  website: string;
  notes: string;
}

type FormErrors = Partial<Record<keyof FormValues, string>>;

function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validate(values: FormValues): FormErrors {
  const errors: FormErrors = {};

  if (!values.name.trim()) {
    errors.name = 'El nombre es obligatorio.';
  }

  if (!values.rut.trim()) {
    errors.rut = 'El RUT es obligatorio.';
  }

  if (!values.email.trim()) {
    errors.email = 'El email es obligatorio.';
  } else if (!isValidEmail(values.email.trim())) {
    errors.email = 'El email no es válido.';
  }

  if (!values.phone.trim()) {
    errors.phone = 'El teléfono es obligatorio.';
  }

  return errors;
}

function extractErrorMessage(error: unknown): string {
  if (error instanceof Error && error.message) {
    return error.message;
  }

  const raw = error as { message?: string; errors?: Record<string, string[]> } | null;

  if (raw?.message) {
    return raw.message;
  }

  const first = raw?.errors ? Object.values(raw.errors).flat()[0] : undefined;

  return first ?? 'Error al crear el cliente.';
}

interface FieldProps {
  id: string;
  label: string;
  error?: string;
  children: ReactNode;
}

function Field({ id, label, error, children }: FieldProps) {
  return (
    <div className="flex flex-col gap-1.5">
      <Label htmlFor={id}>{label}</Label>
      {children}
      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  );
}

function buildInitialValues(client?: Client | null): FormValues {
  return {
    name: client?.name ?? '',
    surname: client?.surname ?? '',
    rut: client?.rut ?? '',
    email: client?.email ?? '',
    phone: client?.phone ?? '',
    address: client?.address ?? '',
    website: client?.website ?? '',
    notes: client?.notes ?? '',
  };
}

export function ClientForm({ client, onSuccess }: ClientFormProps) {
  const [values, setValues] = useState<FormValues>(() => buildInitialValues(client));
  const [errors, setErrors] = useState<FormErrors>({});
  const [bannerError, setBannerError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function updateField<K extends keyof FormValues>(key: K, value: string) {
    setValues((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => ({ ...prev, [key]: undefined }));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const clientErrors = validate(values);
    setErrors(clientErrors);

    if (Object.keys(clientErrors).length > 0) {
      return;
    }

    setSubmitting(true);
    setBannerError(null);

    try {
      const payload = {
        name: values.name.trim(),
        surname: values.surname.trim() || null,
        rut: values.rut.trim(),
        email: values.email.trim(),
        phone: values.phone.trim(),
        address: values.address.trim() || null,
        notes: values.notes.trim() || null,
        website: values.website.trim() || null,
      };

      if (client) {
        await updateClient(client.id, payload);
      } else {
        await createClient(payload);
      }

      onSuccess();
    } catch (error) {
      setBannerError(extractErrorMessage(error));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4" noValidate>
      {bannerError && (
        <div
          role="alert"
          className="rounded-md border border-destructive bg-destructive/10 px-3 py-2 text-sm text-destructive"
        >
          {bannerError}
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field id="name" label="Nombre" error={errors.name}>
          <Input
            id="name"
            value={values.name}
            onChange={(event) => updateField('name', event.target.value)}
            autoFocus
          />
        </Field>

        <Field id="surname" label="Apellido" error={errors.surname}>
          <Input
            id="surname"
            value={values.surname}
            onChange={(event) => updateField('surname', event.target.value)}
          />
        </Field>

        <Field id="rut" label="RUT" error={errors.rut}>
          <Input
            id="rut"
            value={values.rut}
            onChange={(event) => updateField('rut', event.target.value)}
            placeholder="12.345.678-5"
          />
        </Field>

        <Field id="email" label="Email" error={errors.email}>
          <Input
            id="email"
            type="email"
            value={values.email}
            onChange={(event) => updateField('email', event.target.value)}
            placeholder="cliente@example.com"
          />
        </Field>

        <Field id="phone" label="Teléfono" error={errors.phone}>
          <Input
            id="phone"
            value={values.phone}
            onChange={(event) => updateField('phone', event.target.value)}
            placeholder="+56 9 1234 5678"
          />
        </Field>

        <Field id="website" label="Sitio web" error={errors.website}>
          <Input
            id="website"
            value={values.website}
            onChange={(event) => updateField('website', event.target.value)}
            placeholder="https://example.com"
          />
        </Field>

        <Field id="address" label="Dirección" error={errors.address}>
          <Input
            id="address"
            value={values.address}
            onChange={(event) => updateField('address', event.target.value)}
          />
        </Field>
      </div>

      <Field id="notes" label="Notas" error={errors.notes}>
        <Textarea
          id="notes"
          value={values.notes}
          onChange={(event) => updateField('notes', event.target.value)}
          rows={3}
        />
      </Field>

      <div className="flex justify-end">
        <Button type="submit" disabled={submitting}>
          {submitting ? 'Guardando...' : 'Guardar'}
        </Button>
      </div>
    </form>
  );
}
