import { AuthProvider } from '@/context/AuthContext';
import { AppShell } from '@/components/shared/app-shell';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <AuthProvider>
      <AppShell>{children}</AppShell>
    </AuthProvider>
  );
}
