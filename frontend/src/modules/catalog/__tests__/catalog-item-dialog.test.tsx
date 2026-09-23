import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { CatalogItemDialog } from '../components/catalog-item-dialog';
import type { CatalogItemType, CatalogNode } from '../api';

function catalogNode(overrides: Partial<CatalogNode> & { item_type: CatalogItemType }): CatalogNode {
  return {
    id: `${overrides.item_type}-1`,
    base_id: `base-${overrides.item_type}`,
    parent_fork_id: overrides.item_type === 'rubro' ? null : 'parent-1',
    sort_order: 0,
    status: 'activo',
    origin: 'personal',
    overridden_fields: [],
    ...overrides,
  };
}

type DialogProps = ComponentProps<typeof CatalogItemDialog>;

function setup(props: Partial<DialogProps> = {}) {
  const onOpenChange = jest.fn();
  const onSave = jest.fn().mockResolvedValue(undefined);

  render(<CatalogItemDialog open item={null} onOpenChange={onOpenChange} onSave={onSave} {...props} />);

  return { onOpenChange, onSave };
}

describe('CatalogItemDialog — type-aware fields', () => {
  it('opens create mode for a categoria under a rubro with category fields only', () => {
    setup({ createIntent: { type: 'categoria', parentForkId: 'rubro-1' } });

    expect(screen.getByRole('heading', { name: 'New category' })).toBeInTheDocument();
    expect(screen.getByLabelText('Name')).toBeInTheDocument();
    expect(screen.getByLabelText('Description')).toBeInTheDocument();
    expect(screen.queryByLabelText('Title')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Value')).not.toBeInTheDocument();
  });

  it('opens create mode for a service under a categoria with service fields only', () => {
    setup({ createIntent: { type: 'service', parentForkId: 'cat-1' } });

    expect(screen.getByRole('heading', { name: 'New service' })).toBeInTheDocument();
    expect(screen.getByLabelText('Title')).toBeInTheDocument();
    expect(screen.getByLabelText('Description')).toBeInTheDocument();
    expect(screen.getByLabelText('Value')).toBeInTheDocument();
    expect(screen.queryByLabelText('Name')).not.toBeInTheDocument();
  });

  it('opens edit mode for a service prefilled with its resolved values', () => {
    setup({
      item: catalogNode({
        item_type: 'service',
        title: 'Logo Design',
        description: 'Brand logo',
        value: 50000,
      }),
    });

    expect(screen.getByRole('heading', { name: 'Edit service' })).toBeInTheDocument();
    expect(screen.getByLabelText('Title')).toHaveValue('Logo Design');
    expect(screen.getByLabelText('Description')).toHaveValue('Brand logo');
    expect(screen.getByLabelText('Value')).toHaveValue(50000);
  });

  it('opens edit mode for a rubro with name and description only', () => {
    setup({ item: catalogNode({ item_type: 'rubro', name: 'My Rubro', description: 'Custom area' }) });

    expect(screen.getByRole('heading', { name: 'Edit rubro' })).toBeInTheDocument();
    expect(screen.getByLabelText('Name')).toHaveValue('My Rubro');
    expect(screen.queryByLabelText('Title')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Value')).not.toBeInTheDocument();
  });

  it('opens edit mode for a categoria with the category title and category fields only', () => {
    setup({ item: catalogNode({ item_type: 'categoria', name: 'Consulting', description: 'Advisory services' }) });

    expect(screen.getByRole('heading', { name: 'Edit category' })).toBeInTheDocument();
    expect(screen.getByLabelText('Name')).toHaveValue('Consulting');
    expect(screen.queryByLabelText('Title')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Value')).not.toBeInTheDocument();
  });
});

describe('CatalogItemDialog — service tags editor', () => {
  it('presents exactly the five allowed tag options', () => {
    setup({ createIntent: { type: 'service', parentForkId: 'cat-1' } });

    expect(screen.getAllByRole('checkbox')).toHaveLength(5);
    for (const tag of ['frontend', 'backend', 'fullstack', 'devops', 'mobile']) {
      expect(screen.getByRole('checkbox', { name: tag })).toBeInTheDocument();
    }
  });

  it('round-trips existing tags as the selected options', () => {
    setup({ item: catalogNode({ item_type: 'service', title: 'Logo', tags: ['frontend', 'mobile'] }) });

    expect(screen.getByRole('checkbox', { name: 'frontend' })).toBeChecked();
    expect(screen.getByRole('checkbox', { name: 'mobile' })).toBeChecked();
    expect(screen.getByRole('checkbox', { name: 'backend' })).not.toBeChecked();
    expect(screen.getByRole('checkbox', { name: 'fullstack' })).not.toBeChecked();
    expect(screen.getByRole('checkbox', { name: 'devops' })).not.toBeChecked();
  });

  it('submits an empty tags array when no tag is selected', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({ item: catalogNode({ item_type: 'service', title: 'Logo', tags: ['frontend'] }) });

    await user.click(screen.getByRole('checkbox', { name: 'frontend' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onSave).toHaveBeenCalledWith(expect.objectContaining({ tags: [] }));
  });

  it('submits the selected tags in the payload', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({ item: catalogNode({ item_type: 'service', title: 'Logo', tags: [] }) });

    await user.click(screen.getByRole('checkbox', { name: 'frontend' }));
    await user.click(screen.getByRole('checkbox', { name: 'devops' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onSave).toHaveBeenCalledWith(expect.objectContaining({ tags: ['frontend', 'devops'] }));
  });
});

describe('CatalogItemDialog — changed-fields-only submit', () => {
  it('sends only the edited field and omits untouched ones', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({
      item: catalogNode({
        item_type: 'service',
        title: 'Logo',
        description: 'Brand logo',
        value: 50000,
        tags: ['frontend'],
      }),
    });

    await user.clear(screen.getByLabelText('Title'));
    await user.type(screen.getByLabelText('Title'), 'Brand Logo');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    const payload = onSave.mock.calls[0][0];
    expect(payload).toEqual({ title: 'Brand Logo' });
    expect(Object.keys(payload)).toEqual(['title']);
  });

  it('sends an empty payload but still calls the API when nothing changed', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({
      item: catalogNode({ item_type: 'service', title: 'Logo', description: 'Brand logo', value: 50000, tags: [] }),
    });

    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    const payload = onSave.mock.calls[0][0];
    expect(payload).toEqual({});
    expect(Object.keys(payload)).toEqual([]);
  });

  it('sends exactly the touched title and tag fields', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({
      item: catalogNode({ item_type: 'service', title: 'Logo', description: 'Brand logo', value: 50000, tags: [] }),
    });

    await user.clear(screen.getByLabelText('Title'));
    await user.type(screen.getByLabelText('Title'), 'Brand Logo');
    await user.click(screen.getByRole('checkbox', { name: 'frontend' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    const payload = onSave.mock.calls[0][0];
    expect(payload).toEqual({ title: 'Brand Logo', tags: ['frontend'] });
    expect(Object.keys(payload).sort()).toEqual(['tags', 'title']);
  });

  it('sends every type-relevant field plus parent_fork_id in create mode', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({ createIntent: { type: 'service', parentForkId: 'cat-1' } });

    await user.type(screen.getByLabelText('Title'), 'New Service');
    await user.type(screen.getByLabelText('Description'), 'Desc');
    await user.type(screen.getByLabelText('Value'), '10000');
    await user.click(screen.getByRole('checkbox', { name: 'backend' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onSave).toHaveBeenCalledWith({
      title: 'New Service',
      description: 'Desc',
      value: 10000,
      tags: ['backend'],
      parent_fork_id: 'cat-1',
    });
  });
});

describe('CatalogItemDialog — revert-to-base controls', () => {
  it('renders a revert control only for overridden fields', () => {
    setup({
      item: catalogNode({
        item_type: 'service',
        title: 'My Logo',
        value: 60000,
        description: 'Brand logo',
        overridden_fields: ['title', 'value'],
      }),
    });

    expect(screen.getByRole('button', { name: 'Revert title to base' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Revert value to base' })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Revert description to base' })).not.toBeInTheDocument();
  });

  it('clears the field, shows the inheritance placeholder and sends explicit null', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({
      item: catalogNode({
        item_type: 'service',
        title: 'My Logo',
        value: 60000,
        description: 'Brand logo',
        overridden_fields: ['title'],
      }),
    });

    await user.click(screen.getByRole('button', { name: 'Revert title to base' }));

    expect(screen.getByLabelText('Title')).toHaveValue('');
    expect(screen.getByLabelText('Title')).toHaveAttribute('placeholder', 'Inherited from base');

    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    const payload = onSave.mock.calls[0][0];
    expect(payload).toEqual({ title: null });
    expect(Object.keys(payload)).toEqual(['title']);
  });

  it('sends null for a reverted field even when its override equals the base value', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({
      item: catalogNode({ item_type: 'service', title: 'Logo', value: 50000, overridden_fields: ['value'] }),
    });

    await user.click(screen.getByRole('button', { name: 'Revert value to base' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onSave.mock.calls[0][0]).toEqual({ value: null });
  });

  it('sends the manual value when a reverted field is edited again', async () => {
    const user = userEvent.setup();
    const { onSave } = setup({
      item: catalogNode({ item_type: 'service', title: 'My Logo', value: 60000, overridden_fields: ['title'] }),
    });

    await user.click(screen.getByRole('button', { name: 'Revert title to base' }));
    await user.type(screen.getByLabelText('Title'), 'New Title');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onSave.mock.calls[0][0]).toEqual({ title: 'New Title' });
  });
});

describe('CatalogItemDialog — server error surfacing', () => {
  it('renders a 422 field error and keeps the dialog open when the save is rejected', async () => {
    const user = userEvent.setup();
    const { onOpenChange, onSave } = setup({
      item: catalogNode({ item_type: 'categoria', name: 'Consulting' }),
      validationErrors: { name: ['The name has already been taken.'] },
    });
    onSave.mockRejectedValueOnce(new Error('422'));

    expect(screen.getByText('The name has already been taken.')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onOpenChange).not.toHaveBeenCalledWith(false);
    expect(screen.getByText('The name has already been taken.')).toBeInTheDocument();
  });

  it('renders multiple 422 field errors at once', () => {
    setup({
      createIntent: { type: 'service', parentForkId: 'cat-1' },
      validationErrors: {
        title: ['The title field is required.'],
        value: ['The value must be at least 0.'],
      },
    });

    expect(screen.getByText('The title field is required.')).toBeInTheDocument();
    expect(screen.getByText('The value must be at least 0.')).toBeInTheDocument();
  });

  it('renders a 409 conflict as a form-level error and keeps the dialog open', async () => {
    const user = userEvent.setup();
    const { onOpenChange, onSave } = setup({
      item: catalogNode({ item_type: 'rubro', name: 'My Rubro' }),
      formError: 'Duplicate fork identity.',
    });
    onSave.mockRejectedValueOnce(new Error('409'));

    expect(screen.getByRole('alert')).toHaveTextContent('Duplicate fork identity.');

    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(onOpenChange).not.toHaveBeenCalledWith(false);
  });

  it('renders the generic message when an unexpected error occurs', async () => {
    const user = userEvent.setup();
    const { onOpenChange, onSave } = setup({ createIntent: { type: 'service', parentForkId: 'cat-1' } });
    onSave.mockRejectedValueOnce(new Error('HTTP 500'));

    await user.type(screen.getByLabelText('Title'), 'New Service');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => expect(onSave).toHaveBeenCalledTimes(1));
    expect(await screen.findByText('The item could not be saved.')).toBeInTheDocument();
    expect(onOpenChange).not.toHaveBeenCalledWith(false);
  });
});
