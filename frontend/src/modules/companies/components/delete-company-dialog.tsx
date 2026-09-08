'use client';

import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { deleteCompany, getCompany } from '@/modules/companies/api';
import type { Company } from '@/types';

interface DeleteCompanyDialogProps {
  company: Company | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onChanged: () => void;
}

interface CompanyClient {
  id: string;
  name: string;
  surname: string | null;
}

function clientFullName(client: CompanyClient): string {
  return [client.name, client.surname].filter(Boolean).join(' ');
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

  return first ?? 'Error al procesar la solicitud.';
}

export function DeleteCompanyDialog({
  company,
  open,
  onOpenChange,
  onChanged,
}: DeleteCompanyDialogProps) {
  const [clients, setClients] = useState<CompanyClient[]>([]);
  const [loadingClients, setLoadingClients] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const blocked = company !== null && company.clients_count > 0;

  useEffect(() => {
    if (!open) {
      setClients([]);
      setError(null);
      setSubmitting(false);
      setLoadingClients(false);
      return;
    }

    setError(null);
    setSubmitting(false);

    if (!company || company.clients_count === 0) {
      setClients([]);
      setLoadingClients(false);
      return;
    }

    let active = true;

    setLoadingClients(true);
    setClients([]);

    getCompany(company.id)
      .then((data) => {
        if (active) setClients(data.clients);
      })
      .catch((err) => {
        if (active) setError(extractErrorMessage(err));
      })
      .finally(() => {
        if (active) setLoadingClients(false);
      });

    return () => {
      active = false;
    };
  }, [open, company]);

  async function handleDelete() {
    if (!company) return;

    setSubmitting(true);
    setError(null);

    try {
      await deleteCompany(company.id);
      onChanged();
      onOpenChange(false);
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{blocked ? 'No se puede eliminar' : 'Eliminar empresa'}</DialogTitle>
          <DialogDescription>
            {blocked
              ? `Esta empresa tiene ${company?.clients_count} clientes asociados.`
              : company
                ? `¿Eliminar definitivamente «${company.name}»? Esta acción es irreversible.`
                : null}
          </DialogDescription>
        </DialogHeader>

        {error && (
          <div
            role="alert"
            className="rounded-md border border-destructive bg-destructive/10 px-3 py-2 text-sm text-destructive"
          >
            {error}
          </div>
        )}

        {blocked ? (
          <>
            {loadingClients ? (
              <p className="text-sm text-muted-foreground">Cargando clientes...</p>
            ) : clients.length > 0 ? (
              <ul className="max-h-48 list-inside list-disc space-y-1 overflow-y-auto text-sm">
                {clients.map((client) => (
                  <li key={client.id}>{clientFullName(client)}</li>
                ))}
              </ul>
            ) : null}

            <DialogFooter>
              <Button onClick={() => onOpenChange(false)}>Entendido</Button>
            </DialogFooter>
          </>
        ) : (
          <DialogFooter>
            <Button variant="outline" onClick={() => onOpenChange(false)}>
              Cancelar
            </Button>
            <Button variant="destructive" onClick={handleDelete} disabled={submitting}>
              {submitting ? 'Eliminando...' : 'Eliminar'}
            </Button>
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  );
}
