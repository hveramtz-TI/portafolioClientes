'use client';

import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { TableSkeleton } from '@/components/shared/table-skeleton';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { getClients, updateClientStatus } from '@/modules/clients/api';
import { ClientForm } from '@/modules/clients/components/client-form';
import { ClientsTable } from '@/modules/clients/components/clients-table';
import { DeleteClientDialog } from '@/modules/clients/components/delete-client-dialog';
import type { Client } from '@/types';

type StatusFilter = 'activo' | 'desactivado' | 'all';

export default function ClientesPage() {
  const [status, setStatus] = useState<StatusFilter>('activo');
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebouncedValue(search, 300);
  const [clients, setClients] = useState<Client[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshKey, setRefreshKey] = useState(0);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingClient, setEditingClient] = useState<Client | null>(null);
  const [deletingClient, setDeletingClient] = useState<Client | null>(null);

  useEffect(() => {
    let active = true;

    async function load() {
      setLoading(true);
      try {
        const data = await getClients({ status, search: debouncedSearch });
        if (active) setClients(data);
      } catch (error) {
        console.error('Error loading clients:', error);
        if (active) setClients([]);
      } finally {
        if (active) setLoading(false);
      }
    }

    load();

    return () => {
      active = false;
    };
  }, [status, debouncedSearch, refreshKey]);

  async function handleReactivate(client: Client) {
    try {
      await updateClientStatus(client.id, 'activo');
      setRefreshKey((key) => key + 1);
    } catch (error) {
      console.error('Error reactivating client:', error);
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Clientes"
        description="Gestión de clientes del portafolio."
      />

      <div className="flex flex-col gap-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <Tabs
            value={status}
            onValueChange={(value) => setStatus(value as StatusFilter)}
          >
            <TabsList>
              <TabsTrigger value="activo">Activos</TabsTrigger>
              <TabsTrigger value="desactivado">Desactivados</TabsTrigger>
              <TabsTrigger value="all">Todos</TabsTrigger>
            </TabsList>
          </Tabs>

          <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            <Input
              type="search"
              placeholder="Buscar por nombre, RUT o email..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              className="sm:max-w-xs"
            />

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
              <Button
                onClick={() => {
                  setEditingClient(null);
                  setDialogOpen(true);
                }}
              >
                Nuevo Cliente
              </Button>
              <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                  <DialogTitle>
                    {editingClient ? 'Editar Cliente' : 'Nuevo Cliente'}
                  </DialogTitle>
                  <DialogDescription>
                    {editingClient
                      ? 'Modificá los datos del cliente seleccionado.'
                      : 'Completá los datos del cliente para darlo de alta.'}
                  </DialogDescription>
                </DialogHeader>
                <ClientForm
                  client={editingClient}
                  onSuccess={() => {
                    setDialogOpen(false);
                    setRefreshKey((key) => key + 1);
                  }}
                />
              </DialogContent>
            </Dialog>
          </div>
        </div>

        {loading ? (
          <TableSkeleton />
        ) : clients.length === 0 ? (
          <EmptyState message="No hay clientes" />
        ) : (
          <ClientsTable
            clients={clients}
            onEdit={(client) => {
              setEditingClient(client);
              setDialogOpen(true);
            }}
            onDelete={(client) => setDeletingClient(client)}
            onReactivate={handleReactivate}
          />
        )}
      </div>

      <DeleteClientDialog
        client={deletingClient}
        open={deletingClient !== null}
        onOpenChange={(open) => {
          if (!open) setDeletingClient(null);
        }}
        onChanged={() => {
          setDeletingClient(null);
          setRefreshKey((key) => key + 1);
        }}
      />
    </div>
  );
}
