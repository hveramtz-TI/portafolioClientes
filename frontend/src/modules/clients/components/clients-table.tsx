'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Pencil, RefreshCcw, Trash2 } from 'lucide-react';
import type { Client } from '../types';
import { formatRut } from '../utils';

function fullName(client: Client): string {
  return [client.name, client.surname].filter(Boolean).join(' ');
}

interface ClientsTableProps {
  clients: Client[];
  onEdit?: (client: Client) => void;
  onDelete?: (client: Client) => void;
  onReactivate?: (client: Client) => void;
}

export function ClientsTable({ clients, onEdit, onDelete, onReactivate }: ClientsTableProps) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Nombre</TableHead>
          <TableHead>RUT</TableHead>
          <TableHead>Email</TableHead>
          <TableHead>Teléfono</TableHead>
          <TableHead>Estado</TableHead>
          <TableHead className="text-right">Acciones</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {clients.map((client) => (
          <TableRow key={client.id}>
            <TableCell className="font-medium">{fullName(client)}</TableCell>
            <TableCell>{formatRut(client.rut)}</TableCell>
            <TableCell>{client.email}</TableCell>
            <TableCell>{client.phone}</TableCell>
            <TableCell>
              <Badge
                variant={client.status === 'activo' ? 'default' : 'outline'}
              >
                {client.status === 'activo' ? 'Activo' : 'Desactivado'}
              </Badge>
            </TableCell>
            <TableCell className="text-right">
              <Button
                variant="ghost"
                size="icon"
                aria-label={`Editar ${fullName(client)}`}
                onClick={() => onEdit?.(client)}
              >
                <Pencil />
              </Button>
              {client.status === 'activo' ? (
                <Button
                  variant="ghost"
                  size="icon"
                  aria-label={`Eliminar ${fullName(client)}`}
                  onClick={() => onDelete?.(client)}
                >
                  <Trash2 />
                </Button>
              ) : (
                <Button
                  variant="ghost"
                  size="icon"
                  aria-label={`Reactivar ${fullName(client)}`}
                  onClick={() => onReactivate?.(client)}
                >
                  <RefreshCcw />
                </Button>
              )}
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
