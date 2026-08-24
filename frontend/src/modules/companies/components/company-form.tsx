'use client';

import { useState, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { createCompany, updateCompany } from '@/modules/companies/api';
import type { Company } from '@/modules/companies/types';

interface CompanyFormProps {
  company?: Company | null;
  onSuccess: () => void;
}

interface FormValues {
  name: string;
  rut: string;
  email: string;
  phone: string;
  address: string;
  website: string;
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

  return first ?? 'Error al crear la empresa.';
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

function buildInitialValues(company?: Company | null): FormValues {
  return {
    name: company?.name ?? '',
    rut: company?.rut ?? '',
    email: company?.email ?? '',
    phone: company?.phone ?? '',
    address: company?.address ?? '',
    website: company?.website ?? '',
  };
}

export function CompanyForm({ company, onSuccess }: CompanyFormProps) {
  const [values, setValues] = useState<FormValues>(() => buildInitialValues(company));
  const [errors, setErrors] = useState<FormErrors>({});
  const [bannerError, setBannerError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function updateField<K extends keyof FormValues>(key: K, value: string) {
    setValues((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => ({ ...prev, [key]: undefined }));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const companyErrors = validate(values);
    setErrors(companyErrors);

    if (Object.keys(companyErrors).length > 0) {
      return;
    }

    setSubmitting(true);
    setBannerError(null);

    try {
      const payload = {
        name: values.name.trim(),
        rut: values.rut.trim(),
        email: values.email.trim(),
        phone: values.phone.trim(),
        address: values.address.trim() || null,
        website: values.website.trim() || null,
      };

      if (company) {
        await updateCompany(company.id, payload);
      } else {
        await createCompany(payload);
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

        <Field id="rut" label="RUT" error={errors.rut}>
          <Input
            id="rut"
            value={values.rut}
            onChange={(event) => updateField('rut', event.target.value)}
            placeholder="10.101.010-4"
          />
        </Field>

        <Field id="email" label="Email" error={errors.email}>
          <Input
            id="email"
            type="email"
            value={values.email}
            onChange={(event) => updateField('email', event.target.value)}
            placeholder="empresa@example.com"
          />
        </Field>

        <Field id="phone" label="Teléfono" error={errors.phone}>
          <Input
            id="phone"
            value={values.phone}
            onChange={(event) => updateField('phone', event.target.value)}
            placeholder="+56 2 2123 4567"
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

      <div className="flex justify-end">
        <Button type="submit" disabled={submitting}>
          {submitting ? 'Guardando...' : 'Guardar'}
        </Button>
      </div>
    </form>
  );
}
