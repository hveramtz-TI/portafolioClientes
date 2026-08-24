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
import { deleteClient, updateClientStatus } from '@/modules/clients/api';
import type { Client } from '@/modules/clients/types';

interface DeleteClientDialogProps {
  client: Client | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onChanged: () => void;
}

function fullName(client: Client): string {
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

export function DeleteClientDialog({
  client,
  open,
  onOpenChange,
  onChanged,
}: DeleteClientDialogProps) {
  const [step, setStep] = useState<'options' | 'confirm'>('options');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) {
      setStep('options');
      setError(null);
    }
  }, [open]);

  async function handleDeactivate() {
    if (!client) return;

    setSubmitting(true);
    setError(null);

    try {
      await updateClientStatus(client.id, 'desactivado');
      onChanged();
      onOpenChange(false);
    } catch (err) {
      setError(extractErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleDelete() {
    if (!client) return;

    setSubmitting(true);
    setError(null);

    try {
      await deleteClient(client.id);
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
          <DialogTitle>
            {step === 'confirm' ? '¿Eliminar definitivamente?' : 'Eliminar cliente'}
          </DialogTitle>
          <DialogDescription>
            {step === 'confirm'
              ? 'Esta acción es irreversible y no se puede deshacer.'
              : client
                ? `¿Qué querés hacer con «${fullName(client)}»?`
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

        {step === 'options' ? (
          <DialogFooter>
            <Button variant="outline" onClick={() => onOpenChange(false)}>
              Cancelar
            </Button>
            <Button variant="secondary" onClick={handleDeactivate} disabled={submitting}>
              Desactivar
            </Button>
            <Button
              variant="destructive"
              onClick={() => setStep('confirm')}
              disabled={submitting}
            >
              Eliminar definitivamente
            </Button>
          </DialogFooter>
        ) : (
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setStep('options')}
              disabled={submitting}
            >
              Volver
            </Button>
            <Button variant="destructive" onClick={handleDelete} disabled={submitting}>
              Eliminar definitivamente
            </Button>
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  );
}
