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
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { TableSkeleton } from '@/components/shared/table-skeleton';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { getCompanies } from '@/modules/companies/api';
import { CompanyForm } from '@/modules/companies/components/company-form';
import { CompaniesTable } from '@/modules/companies/components/companies-table';
import { DeleteCompanyDialog } from '@/modules/companies/components/delete-company-dialog';
import type { Company } from '@/types';

export default function EmpresasPage() {
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebouncedValue(search, 300);
  const [companies, setCompanies] = useState<Company[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshKey, setRefreshKey] = useState(0);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingCompany, setEditingCompany] = useState<Company | null>(null);
  const [deletingCompany, setDeletingCompany] = useState<Company | null>(null);

  useEffect(() => {
    let active = true;

    async function load() {
      setLoading(true);
      try {
        const data = await getCompanies({ search: debouncedSearch });
        if (active) setCompanies(data);
      } catch (error) {
        console.error('Error loading companies:', error);
        if (active) setCompanies([]);
      } finally {
        if (active) setLoading(false);
      }
    }

    load();

    return () => {
      active = false;
    };
  }, [debouncedSearch, refreshKey]);

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Empresas"
        description="Gestión de empresas del portafolio."
      />

      <div className="flex flex-col gap-4">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
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
                setEditingCompany(null);
                setDialogOpen(true);
              }}
            >
              Nueva Empresa
            </Button>
            <DialogContent className="sm:max-w-xl">
              <DialogHeader>
                <DialogTitle>
                  {editingCompany ? 'Editar Empresa' : 'Nueva Empresa'}
                </DialogTitle>
                <DialogDescription>
                  {editingCompany
                    ? 'Modificá los datos de la empresa seleccionada.'
                    : 'Completá los datos de la empresa para darla de alta.'}
                </DialogDescription>
              </DialogHeader>
              <CompanyForm
                company={editingCompany}
                onSuccess={() => {
                  setDialogOpen(false);
                  setRefreshKey((key) => key + 1);
                }}
              />
            </DialogContent>
          </Dialog>
        </div>

        {loading ? (
          <TableSkeleton />
        ) : companies.length === 0 ? (
          <EmptyState message="No hay empresas" />
        ) : (
          <CompaniesTable
            companies={companies}
            onEdit={(company) => {
              setEditingCompany(company);
              setDialogOpen(true);
            }}
            onDelete={(company) => setDeletingCompany(company)}
          />
        )}
      </div>

      <DeleteCompanyDialog
        company={deletingCompany}
        open={deletingCompany !== null}
        onOpenChange={(open) => {
          if (!open) setDeletingCompany(null);
        }}
        onChanged={() => {
          setDeletingCompany(null);
          setRefreshKey((key) => key + 1);
        }}
      />
    </div>
  );
}
