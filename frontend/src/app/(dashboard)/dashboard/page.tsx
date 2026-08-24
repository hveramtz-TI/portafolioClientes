'use client';

import { useCallback, useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/context/AuthContext';
import { api, User } from '@/lib/api';

export default function DashboardPage() {
  const { user } = useAuth();
  const [users, setUsers] = useState<User[]>([]);
  const [loadingUsers, setLoadingUsers] = useState(false);

  const loadUsers = useCallback(async () => {
    setLoadingUsers(true);
    try {
      const data = await api.getUsers();
      setUsers(data);
    } catch (err) {
      console.error('Error loading users:', err);
    } finally {
      setLoadingUsers(false);
    }
  }, []);

  useEffect(() => {
    if (user?.role === 'admin') {
      loadUsers();
    }
  }, [user, loadUsers]);

  if (!user) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <p className="text-sm text-muted-foreground">Cargando...</p>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-muted-foreground">
          Resumen general de tu cuenta.
        </p>
      </div>

      <section className="rounded-lg border bg-card p-6">
        <h2 className="text-base font-medium">Información del Usuario</h2>
        <dl className="mt-4 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
          <div>
            <dt className="text-sm font-medium text-muted-foreground">Nombre</dt>
            <dd className="mt-1 text-sm">{user.name}</dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-muted-foreground">Email</dt>
            <dd className="mt-1 text-sm">{user.email}</dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-muted-foreground">Rol</dt>
            <dd className="mt-1 text-sm capitalize">{user.role}</dd>
          </div>
          <div>
            <dt className="text-sm font-medium text-muted-foreground">ID</dt>
            <dd className="mt-1 font-mono text-xs">{user.id}</dd>
          </div>
        </dl>
      </section>

      {user.role === 'admin' && (
        <section className="rounded-lg border bg-card p-6">
          <h2 className="text-base font-medium">
            Lista de Usuarios (Solo Admin)
          </h2>
          <div className="mt-4">
            {loadingUsers ? (
              <p className="text-sm text-muted-foreground">
                Cargando usuarios...
              </p>
            ) : (
              <ul className="divide-y">
                {users.map((u) => (
                  <li
                    key={u.id}
                    className="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
                  >
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium">{u.name}</p>
                      <p className="truncate text-sm text-muted-foreground">
                        {u.email}
                      </p>
                    </div>
                    <Badge variant="secondary" className="capitalize">
                      {u.role}
                    </Badge>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </section>
      )}
    </div>
  );
}
