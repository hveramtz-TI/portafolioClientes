import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { AttachDialog } from '../components/attach-dialog';
import type { CatalogItemType, CatalogNode } from '../api';

function node(overrides: Partial<CatalogNode> & { item_type: CatalogItemType }): CatalogNode {
  return {
    id: `${overrides.item_type}-1`,
    base_id: `base-${overrides.item_type}`,
    parent_fork_id: null,
    sort_order: 0,
    status: 'activo',
    origin: 'personal',
    overridden_fields: [],
    ...overrides,
  };
}

const rubro = node({ item_type: 'rubro', id: 'rubro-1', name: 'My Business' });
const categoria = node({ item_type: 'categoria', id: 'cat-1', name: 'Web', parent_fork_id: 'rubro-1' });
const tree: CatalogNode[] = [{ ...rubro, children: [categoria] }];

const orphanCategoria = node({ item_type: 'categoria', id: 'cat-orphan', name: 'Consulting' });
const orphanService = node({ item_type: 'service', id: 'svc-orphan', title: 'Quick Setup' });

type AttachProps = ComponentProps<typeof AttachDialog>;

function setup(props: Partial<AttachProps> = {}) {
  const onAttach = jest.fn();
  const onCancel = jest.fn();

  render(
    <AttachDialog
      open
      orphan={orphanCategoria}
      tree={tree}
      onAttach={onAttach}
      onCancel={onCancel}
      {...props}
    />,
  );

  return { onAttach, onCancel };
}

describe('AttachDialog — type-coherent parent candidates', () => {
  it('lists rubro forks for an orphan categoria and excludes categorias', async () => {
    const user = userEvent.setup();
    setup({ orphan: orphanCategoria });

    await user.click(screen.getByLabelText('Parent'));

    expect(await screen.findByRole('option', { name: 'My Business' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Web' })).not.toBeInTheDocument();
  });

  it('lists categoria forks for an orphan service and excludes rubros', async () => {
    const user = userEvent.setup();
    setup({ orphan: orphanService });

    await user.click(screen.getByLabelText('Parent'));

    expect(await screen.findByRole('option', { name: 'Web' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'My Business' })).not.toBeInTheDocument();
  });

  it('calls onAttach with the chosen parent fork id on confirm', async () => {
    const user = userEvent.setup();
    const { onAttach } = setup({ orphan: orphanCategoria });

    await user.click(screen.getByLabelText('Parent'));
    await user.click(await screen.findByRole('option', { name: 'My Business' }));
    await user.click(screen.getByRole('button', { name: 'Attach' }));

    expect(onAttach).toHaveBeenCalledWith('rubro-1');
  });

  it('does not call onAttach when cancelled', async () => {
    const user = userEvent.setup();
    const { onAttach, onCancel } = setup({ orphan: orphanCategoria });

    await user.click(screen.getByRole('button', { name: 'Cancel' }));

    expect(onCancel).toHaveBeenCalledTimes(1);
    expect(onAttach).not.toHaveBeenCalled();
  });
});
