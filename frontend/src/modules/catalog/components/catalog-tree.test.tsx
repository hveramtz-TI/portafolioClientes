import { render, screen } from '@testing-library/react';
import { CatalogTree } from './catalog-tree';
import type { CatalogNode } from '../api';

const node: CatalogNode = {
  id: 'rubro-1', base_id: 'base-1', item_type: 'rubro', parent_fork_id: null,
  sort_order: 0, name: 'Design', description: null, status: 'desactivado',
  origin: 'override', overridden_fields: ['name'], children: [{
    id: 'service-1', base_id: 'base-service', item_type: 'service', parent_fork_id: 'rubro-1',
    sort_order: 0, title: 'Logo', description: null, value: 100, tags: ['design'],
    status: 'desactivado', origin: 'base', overridden_fields: [],
  }],
};

describe('CatalogTree', () => {
  it('renders descendants and effective status/origin badges', () => {
    render(<CatalogTree nodes={[node]} onEdit={jest.fn()} onStatus={jest.fn()} onDelete={jest.fn()} />);

    expect(screen.getByRole('heading', { name: 'Design' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Logo' })).toBeInTheDocument();
    expect(screen.getAllByText('Inactive')).toHaveLength(2);
    expect(screen.getByText('Override')).toBeInTheDocument();
    expect(screen.getByText('Base')).toBeInTheDocument();
  });
});
