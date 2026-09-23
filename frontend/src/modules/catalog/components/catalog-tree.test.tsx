import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { CatalogTree } from './catalog-tree';
import type { CatalogItemType, CatalogNode } from '../api';

const node: CatalogNode = {
  id: 'rubro-1', base_id: 'base-1', item_type: 'rubro', parent_fork_id: null,
  sort_order: 0, name: 'Design', description: null, status: 'desactivado',
  origin: 'override', overridden_fields: ['name'], children: [{
    id: 'service-1', base_id: 'base-service', item_type: 'service', parent_fork_id: 'rubro-1',
    sort_order: 0, title: 'Logo', description: null, value: 100, tags: ['design'],
    status: 'desactivado', origin: 'base', overridden_fields: [],
  }],
};

type TreeProps = ComponentProps<typeof CatalogTree>;

function setup(props: Partial<TreeProps> = {}) {
  const onAddChild = jest.fn();
  const onAttach = jest.fn();
  const view = render(
    <CatalogTree
      nodes={[node]}
      onEdit={jest.fn()}
      onStatus={jest.fn()}
      onDelete={jest.fn()}
      onAddChild={onAddChild}
      onAttach={onAttach}
      {...props}
    />,
  );

  return { onAddChild, onAttach, ...view };
}

describe('CatalogTree', () => {
  it('renders descendants and effective status/origin badges', () => {
    setup();

    expect(screen.getByRole('heading', { name: 'Design' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Logo' })).toBeInTheDocument();
    expect(screen.getAllByText('Inactive')).toHaveLength(2);
    expect(screen.getByText('Override')).toBeInTheDocument();
    expect(screen.getByText('Base')).toBeInTheDocument();
  });
});

const rubroNode: CatalogNode = {
  id: 'rubro-1', base_id: 'base-rubro', item_type: 'rubro', parent_fork_id: null,
  sort_order: 0, name: 'Informática', description: null, status: 'activo',
  origin: 'personal', overridden_fields: [], children: [{
    id: 'cat-1', base_id: 'base-cat', item_type: 'categoria', parent_fork_id: 'rubro-1',
    sort_order: 0, name: 'Web Development', description: null, status: 'activo',
    origin: 'personal', overridden_fields: [], children: [{
      id: 'service-1', base_id: 'base-service', item_type: 'service', parent_fork_id: 'cat-1',
      sort_order: 0, title: 'Logo Design', description: null, value: 100, tags: [],
      status: 'activo', origin: 'personal', overridden_fields: [],
    }],
  }],
};

describe('CatalogTree — child-add affordances', () => {
  it('renders an "Add category" button on a rubro node', () => {
    setup({ nodes: [rubroNode] });

    expect(screen.getByRole('button', { name: 'Add category' })).toBeInTheDocument();
  });

  it('renders an "Add service" button on a categoria node', () => {
    setup({ nodes: [rubroNode] });

    expect(screen.getByRole('button', { name: 'Add service' })).toBeInTheDocument();
  });

  it('renders no add-child button on a service node', () => {
    setup({ nodes: [rubroNode] });

    // The only add affordances belong to the rubro and categoria ancestors.
    expect(screen.getAllByRole('button', { name: /^Add (category|service)$/ })).toHaveLength(2);
  });

  it('fires onAddChild with the rubro node and the categoria child type', async () => {
    const user = userEvent.setup();
    const { onAddChild } = setup({ nodes: [rubroNode] });

    await user.click(screen.getByRole('button', { name: 'Add category' }));

    expect(onAddChild).toHaveBeenCalledWith(rubroNode, 'categoria');
  });

  it('fires onAddChild with the categoria node and the service child type', async () => {
    const user = userEvent.setup();
    const { onAddChild } = setup({ nodes: [rubroNode] });

    await user.click(screen.getByRole('button', { name: 'Add service' }));

    expect(onAddChild).toHaveBeenCalledWith(rubroNode.children?.[0], 'service');
  });

  it('omits add-child buttons for a bare service tree', () => {
    const childType: CatalogItemType = 'service';
    const orphanService: CatalogNode = {
      ...(rubroNode.children?.[0]?.children?.[0] as CatalogNode),
      item_type: childType,
    };

    setup({ nodes: [orphanService] });

    expect(screen.queryByRole('button', { name: /^Add (category|service)$/ })).not.toBeInTheDocument();
  });
});

const orphanCategoria: CatalogNode = {
  id: 'cat-orphan', base_id: 'base-orphan', item_type: 'categoria', parent_fork_id: null,
  sort_order: 0, name: 'Consulting', description: null, status: 'activo',
  origin: 'personal', overridden_fields: [],
};

describe('CatalogTree — orphan attach affordances', () => {
  it('shows an unattached indicator and an Attach action on an orphan categoria', () => {
    setup({ nodes: [orphanCategoria] });

    expect(screen.getByText('Unattached')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Attach Consulting' })).toBeInTheDocument();
  });

  it('does not treat a parentless rubro as an orphan', () => {
    setup({ nodes: [rubroNode] });

    // The rubro has parent_fork_id null, but rubros are roots by design.
    expect(screen.queryByText('Unattached')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /^Attach / })).not.toBeInTheDocument();
  });

  it('does not show orphan affordances for a node with a parent', () => {
    const attached: CatalogNode = { ...orphanCategoria, parent_fork_id: 'rubro-1' };

    setup({ nodes: [attached] });

    expect(screen.queryByText('Unattached')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /^Attach / })).not.toBeInTheDocument();
  });

  it('fires onAttach with the orphan node when Attach is clicked', async () => {
    const user = userEvent.setup();
    const { onAttach } = setup({ nodes: [orphanCategoria] });

    await user.click(screen.getByRole('button', { name: 'Attach Consulting' }));

    expect(onAttach).toHaveBeenCalledWith(orphanCategoria);
  });
});

function serviceNode(overrides: Partial<CatalogNode> = {}): CatalogNode {
  return {
    id: 'service-1', base_id: 'base-svc', item_type: 'service', parent_fork_id: 'cat-1',
    sort_order: 0, title: 'Logo', description: null, value: 100, tags: ['frontend'],
    status: 'activo', origin: 'base', overridden_fields: [],
    ...overrides,
  };
}

describe('CatalogTree — per-field override display', () => {
  it('shows an override indicator for each overridden field', () => {
    setup({ nodes: [serviceNode({ overridden_fields: ['title'] })] });

    expect(screen.getByText('Title override')).toBeInTheDocument();
    expect(screen.queryByText('Value override')).not.toBeInTheDocument();
  });

  it('shows no per-field indicators when nothing is overridden and keeps the origin badge', () => {
    setup({ nodes: [serviceNode({ overridden_fields: [], origin: 'base' })] });

    expect(screen.queryByText(/override$/i)).not.toBeInTheDocument();
    expect(screen.getByText('Base')).toBeInTheDocument();
  });

  it('shows an indicator for every overridden field', () => {
    setup({ nodes: [serviceNode({ overridden_fields: ['title', 'value', 'tags'] })] });

    expect(screen.getByText('Title override')).toBeInTheDocument();
    expect(screen.getByText('Value override')).toBeInTheDocument();
    expect(screen.getByText('Tags override')).toBeInTheDocument();
  });
});
