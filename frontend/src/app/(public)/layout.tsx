import type { CSSProperties } from 'react';
import { Inter } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-inter',
  weight: 'variable',
});

const landingFont: CSSProperties = {
  '--font-sans': 'var(--font-inter)',
} as CSSProperties;

export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className={`${inter.variable} tmc-landing font-sans`} style={landingFont}>
      {children}
    </div>
  );
}
