import Link from 'next/link';

const columns = [
  {
    title: 'Producto',
    links: ['Qué hace', 'Por qué TMC', 'Comenzar'],
  },
  {
    title: 'Recursos',
    links: ['Ingresar', 'Documentación', 'Soporte'],
  },
  {
    title: 'Compañía',
    links: ['Acerca de', 'Contacto'],
  },
  {
    title: 'Legal',
    links: ['Privacidad', 'Términos'],
  },
];

export function LandingFooter() {
  return (
    <footer className="bg-tmc-ink text-tmc-canvas">
      <div className="mx-auto max-w-[1200px] px-4 py-16">
        <div className="mb-12 flex items-center gap-2">
          <span className="text-base font-medium" style={{ fontWeight: 500 }}>
            TMC
          </span>
          <span className="text-sm text-tmc-canvas/70">That&apos;s My Client</span>
        </div>

        <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
          {columns.map((column) => (
            <div key={column.title}>
              <h3 className="mb-4 text-sm font-bold text-tmc-canvas">
                {column.title}
              </h3>
              <ul className="space-y-2">
                {column.links.map((link) => (
                  <li key={link}>
                    <Link
                      href="/"
                      className="text-sm text-tmc-canvas/70 transition-colors hover:text-tmc-canvas focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tmc-accent-light"
                    >
                      {link}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </footer>
  );
}
