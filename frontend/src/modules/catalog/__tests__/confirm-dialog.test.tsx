import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { ConfirmDialog } from '../components/confirm-dialog';
import type { CatalogNode } from '../api';

function catalogNode(overrides: Partial<CatalogNode> = {}): CatalogNode {
  return {
    id: 'rubro-1',
    base_id: 'base-1',
    item_type: 'rubro',
    parent_fork_id: null,
    sort_order: 0,
    name: 'Informática',
    description: null,
    status: 'activo',
    origin: 'personal',
    overridden_fields: [],
    ...overrides,
  };
}

const rubroWithChildren = catalogNode({
  children: [
    catalogNode({ id: 'cat-1', item_type: 'categoria', parent_fork_id: 'rubro-1', name: 'Web Development' }),
    catalogNode({ id: 'cat-2', item_type: 'categoria', parent_fork_id: 'rubro-1', name: 'Consulting' }),
  ],
});

const leafService = catalogNode({
  id: 'service-1',
  item_type: 'service',
  parent_fork_id: 'cat-1',
  name: undefined,
  title: 'Logo Design',
  value: 50000,
  tags: [],
});

type ConfirmProps = ComponentProps<typeof ConfirmDialog>;

function setup(props: Partial<ConfirmProps> = {}) {
  const onConfirm = jest.fn();
  const onCancel = jest.fn();

  render(
    <ConfirmDialog
      open
      item={rubroWithChildren}
      action="delete"
      onConfirm={onConfirm}
      onCancel={onCancel}
      {...props}
    />,
  );

  return { onConfirm, onCancel };
}

describe('ConfirmDialog — differentiated lifecycle wording', () => {
  it('warns that descendants will be permanently deleted for a node with children', () => {
    setup({ item: rubroWithChildren, action: 'delete' });

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText(/and its descendants will be permanently deleted/i)).toBeInTheDocument();
  });

  it('warns only about the element for a leaf node on delete', () => {
    setup({ item: leafService, action: 'delete' });

    expect(screen.getByText(/will be permanently deleted/i)).toBeInTheDocument();
    expect(screen.queryByText(/descendants/i)).not.toBeInTheDocument();
  });

  it('warns that descendants will be deactivated for a node with children', () => {
    setup({ item: rubroWithChildren, action: 'deactivate' });

    expect(screen.getByText(/and its descendants will be deactivated/i)).toBeInTheDocument();
  });

  it('warns only about the element for a leaf node on deactivate', () => {
    setup({ item: leafService, action: 'deactivate' });

    expect(screen.getByText(/will be deactivated/i)).toBeInTheDocument();
    expect(screen.queryByText(/descendants/i)).not.toBeInTheDocument();
  });

  it('shows the item display name', () => {
    setup({ item: leafService, action: 'delete' });

    expect(screen.getByText(/Logo Design/)).toBeInTheDocument();
  });
});

describe('ConfirmDialog — callbacks and mechanism', () => {
  it('calls onConfirm and not window.confirm when confirmed', async () => {
    const user = userEvent.setup();
    const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(true);
    const { onConfirm } = setup({ item: leafService, action: 'delete' });

    await user.click(screen.getByRole('button', { name: 'Delete' }));

    expect(onConfirm).toHaveBeenCalledTimes(1);
    expect(confirmSpy).not.toHaveBeenCalled();
    confirmSpy.mockRestore();
  });

  it('calls onConfirm when a deactivation is confirmed', async () => {
    const user = userEvent.setup();
    const { onConfirm } = setup({ item: rubroWithChildren, action: 'deactivate' });

    await user.click(screen.getByRole('button', { name: 'Deactivate' }));

    expect(onConfirm).toHaveBeenCalledTimes(1);
  });

  it('calls onCancel and does not confirm when cancelled', async () => {
    const user = userEvent.setup();
    const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(true);
    const { onConfirm, onCancel } = setup({ item: leafService, action: 'delete' });

    await user.click(screen.getByRole('button', { name: 'Cancel' }));

    expect(onCancel).toHaveBeenCalledTimes(1);
    expect(onConfirm).not.toHaveBeenCalled();
    expect(confirmSpy).not.toHaveBeenCalled();
    confirmSpy.mockRestore();
  });
});
