import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CatalogoPage from '@/app/(dashboard)/catalogo/page';
import { ApiError } from '@/lib/api';
import {
  createPersonalItem,
  deletePersonalItem,
  forkBaseItem,
  getBaseRubros,
  getUserCatalogTree,
  updatePersonalItem,
  updatePersonalItemStatus,
} from '../api';
import type { CatalogNode } from '../api';

// The `@/` alias resolves for imports but not inside `jest.mock` in this setup,
// so the mock is registered through the equivalent relative path.
jest.mock('../api', () => ({
  getBaseRubros: jest.fn(),
  getUserCatalogTree: jest.fn(),
  forkBaseItem: jest.fn(),
  createPersonalItem: jest.fn(),
  updatePersonalItem: jest.fn(),
  updatePersonalItemStatus: jest.fn(),
  deletePersonalItem: jest.fn(),
}));

const mockAuth = jest.fn();
jest.mock('../../../hooks/useAuth', () => ({ useAuth: () => mockAuth() }));

function authValue(role: 'admin' | 'user') {
  return {
    user: { role },
    loading: false,
    error: null,
    login: jest.fn(),
    logout: jest.fn(),
    refreshUser: jest.fn(),
  };
}

const tree: CatalogNode[] = [
  {
    id: 'rubro-1', base_id: 'base-rubro', item_type: 'rubro', parent_fork_id: null, sort_order: 0,
    name: 'Informática', description: null, status: 'activo', origin: 'personal', overridden_fields: [],
    children: [
      {
        id: 'cat-1', base_id: 'base-cat', item_type: 'categoria', parent_fork_id: 'rubro-1', sort_order: 0,
        name: 'Web Development', description: null, status: 'activo', origin: 'personal', overridden_fields: [],
        children: [
          {
            id: 'service-1', base_id: 'base-svc', item_type: 'service', parent_fork_id: 'cat-1', sort_order: 0,
            title: 'Logo Design', description: null, value: 50000, tags: [], status: 'activo',
            origin: 'personal', overridden_fields: [],
          },
        ],
      },
    ],
  },
];

const rubroNode = tree[0];
const categoriaNode = tree[0].children?.[0] as CatalogNode;

beforeEach(() => {
  jest.clearAllMocks();
  mockAuth.mockReturnValue(authValue('user'));
  jest.mocked(getBaseRubros).mockResolvedValue([
    { id: 'base-rubro', name: 'Base Design', description: null, status: 'activo' },
  ]);
  jest.mocked(getUserCatalogTree).mockResolvedValue(tree);
  jest.mocked(createPersonalItem).mockResolvedValue(rubroNode);
  jest.mocked(updatePersonalItem).mockResolvedValue(rubroNode);
  jest.mocked(updatePersonalItemStatus).mockResolvedValue(rubroNode);
  jest.mocked(deletePersonalItem).mockResolvedValue(undefined);
});

/** Renders the page and opens the My Catalog tab, where the tree lives. */
async function renderPage() {
  const user = userEvent.setup();
  render(<CatalogoPage />);

  await waitFor(() => expect(getUserCatalogTree).toHaveBeenCalledTimes(1));
  await user.click(screen.getByRole('tab', { name: 'My Catalog' }));
  await screen.findByRole('heading', { name: 'Informática' });

  return user;
}

describe('CatalogoPage — child creation through the tree', () => {
  it('opens the categoria dialog from a rubro node and creates the child with its parent', async () => {
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Add category' }));

    expect(await screen.findByRole('heading', { name: 'New category' })).toBeInTheDocument();

    await user.type(screen.getByLabelText('Name'), 'Consulting');
    await user.type(screen.getByLabelText('Description'), 'Advisory services');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(createPersonalItem).toHaveBeenCalledWith('categoria', {
        name: 'Consulting',
        description: 'Advisory services',
        parent_fork_id: 'rubro-1',
      }),
    );
  });

  it('opens the service dialog from a categoria node and creates the child with its parent', async () => {
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Add service' }));

    expect(await screen.findByRole('heading', { name: 'New service' })).toBeInTheDocument();

    await user.type(screen.getByLabelText('Title'), 'Quick Setup');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(createPersonalItem).toHaveBeenCalledWith(
        'service',
        expect.objectContaining({ title: 'Quick Setup', parent_fork_id: 'cat-1' }),
      ),
    );
  });
});

describe('CatalogoPage — differentiated delete confirmation', () => {
  it('opens the confirm dialog with descendant wording and does not call the API yet', async () => {
    const user = await renderPage();
    const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(true);

    await user.click(screen.getByRole('button', { name: 'Delete Informática' }));

    expect(await screen.findByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText(/and its descendants will be permanently deleted/i)).toBeInTheDocument();
    expect(deletePersonalItem).not.toHaveBeenCalled();
    expect(confirmSpy).not.toHaveBeenCalled();

    confirmSpy.mockRestore();
  });

  it('makes no API call when the confirmation is cancelled', async () => {
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Delete Informática' }));
    await user.click(await screen.findByRole('button', { name: 'Cancel' }));

    expect(deletePersonalItem).not.toHaveBeenCalled();
  });

  it('deletes the node and reloads the tree when confirmed', async () => {
    const user = await renderPage();
    const callsBefore = jest.mocked(getUserCatalogTree).mock.calls.length;

    await user.click(screen.getByRole('button', { name: 'Delete Informática' }));
    await user.click(await screen.findByRole('button', { name: 'Delete' }));

    await waitFor(() => expect(deletePersonalItem).toHaveBeenCalledWith('rubro', 'rubro-1'));
    await waitFor(() => expect(jest.mocked(getUserCatalogTree).mock.calls.length).toBeGreaterThan(callsBefore));
  });

  it('confirms a deactivation with descendant wording before changing the status', async () => {
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Deactivate Informática' }));

    expect(await screen.findByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText(/and its descendants will be deactivated/i)).toBeInTheDocument();
    expect(updatePersonalItemStatus).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', { name: 'Deactivate' }));

    await waitFor(() => expect(updatePersonalItemStatus).toHaveBeenCalledWith('rubro', 'rubro-1', 'desactivado'));
  });
});

describe('CatalogoPage — server error surfacing', () => {
  it('maps a 422 response to a field error and keeps the dialog open', async () => {
    const user = await renderPage();
    jest
      .mocked(createPersonalItem)
      .mockRejectedValueOnce(
        new ApiError('Validation failed', 422, { name: ['The name has already been taken.'] }),
      );

    await user.click(screen.getByRole('button', { name: 'Add category' }));
    await user.type(screen.getByLabelText('Name'), 'Consulting');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    expect(await screen.findByText('The name has already been taken.')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'New category' })).toBeInTheDocument();
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('updates an existing node through updatePersonalItem, not create', async () => {
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Edit Web Development' }));
    await user.clear(screen.getByLabelText('Name'));
    await user.type(screen.getByLabelText('Name'), 'Web Consulting');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(updatePersonalItem).toHaveBeenCalledWith('categoria', 'cat-1', { name: 'Web Consulting' }),
    );
    expect(createPersonalItem).not.toHaveBeenCalled();
    expect(categoriaNode.id).toBe('cat-1');
  });
});

const orphanTree: CatalogNode[] = [
  ...tree,
  {
    id: 'cat-orphan', base_id: 'base-orphan', item_type: 'categoria', parent_fork_id: null, sort_order: 1,
    name: 'Consulting', description: null, status: 'activo', origin: 'personal', overridden_fields: [],
  },
];

const moveTree: CatalogNode[] = [
  {
    ...rubroNode,
    children: [
      categoriaNode,
      {
        id: 'cat-branding', base_id: 'base-branding', item_type: 'categoria', parent_fork_id: 'rubro-1', sort_order: 1,
        name: 'Branding', description: null, status: 'activo', origin: 'personal', overridden_fields: [],
      },
    ],
  },
];

describe('CatalogoPage — orphan attach flow', () => {
  it('attaches an orphan categoria to a rubro and reloads the tree', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(orphanTree);
    const user = await renderPage();
    const callsBefore = jest.mocked(getUserCatalogTree).mock.calls.length;

    await user.click(screen.getByRole('button', { name: 'Attach Consulting' }));
    await user.click(screen.getByLabelText('Parent'));
    await user.click(await screen.findByRole('option', { name: 'Informática' }));
    await user.click(screen.getByRole('button', { name: 'Attach' }));

    await waitFor(() =>
      expect(updatePersonalItem).toHaveBeenCalledWith('categoria', 'cat-orphan', { parent_fork_id: 'rubro-1' }),
    );
    await waitFor(() => expect(jest.mocked(getUserCatalogTree).mock.calls.length).toBeGreaterThan(callsBefore));
  });

  it('surfaces the server 422 message when an attach fails', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(orphanTree);
    jest
      .mocked(updatePersonalItem)
      .mockRejectedValueOnce(
        new ApiError('Validation failed', 422, { parent_fork_id: ['Destination title clash.'] }),
      );
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Attach Consulting' }));
    await user.click(screen.getByLabelText('Parent'));
    await user.click(await screen.findByRole('option', { name: 'Informática' }));
    await user.click(screen.getByRole('button', { name: 'Attach' }));

    expect(await screen.findByText('Destination title clash.')).toBeInTheDocument();
  });
});

describe('CatalogoPage — move-service flow', () => {
  it('moves a service to another categoria and reloads the tree', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(moveTree);
    const user = await renderPage();
    const callsBefore = jest.mocked(getUserCatalogTree).mock.calls.length;

    await user.click(screen.getByRole('button', { name: 'Edit Logo Design' }));
    await user.click(screen.getByLabelText('Destination category'));
    await user.click(await screen.findByRole('option', { name: 'Branding' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(updatePersonalItem).toHaveBeenCalledWith('service', 'service-1', { parent_fork_id: 'cat-branding' }),
    );
    await waitFor(() => expect(jest.mocked(getUserCatalogTree).mock.calls.length).toBeGreaterThan(callsBefore));
  });
});

describe('CatalogoPage — admin role-coherent rendering', () => {
  it('hides personalization affordances and shows an informational state for an admin', async () => {
    mockAuth.mockReturnValue(authValue('admin'));
    const user = userEvent.setup();
    render(<CatalogoPage />);

    expect(await screen.findByText('Base Design')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Select' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'New personal rubro' })).not.toBeInTheDocument();

    await user.click(screen.getByRole('tab', { name: 'My Catalog' }));

    expect(await screen.findByText(/personalization is for non-admin users/i)).toBeInTheDocument();
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    expect(
      screen.queryByRole('button', { name: /^(Edit|Delete|Deactivate|Reactivate|Add|Attach)\b/ }),
    ).not.toBeInTheDocument();
  });

  it('keeps the personalization affordances for a non-admin user', async () => {
    const user = userEvent.setup();
    render(<CatalogoPage />);

    expect(await screen.findByText('Base Design')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Select' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'New personal rubro' })).toBeInTheDocument();

    await user.click(screen.getByRole('tab', { name: 'My Catalog' }));
    await screen.findByRole('heading', { name: 'Informática' });

    expect(screen.getByRole('button', { name: 'Add category' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Edit Web Development' })).toBeInTheDocument();
  });
});

const detailTree: CatalogNode[] = [
  {
    id: 'rubro-1', base_id: 'base-rubro', item_type: 'rubro', parent_fork_id: null, sort_order: 0,
    name: 'Informática', description: null, status: 'activo', origin: 'personal', overridden_fields: [],
    children: [
      {
        id: 'cat-1', base_id: 'base-cat', item_type: 'categoria', parent_fork_id: 'rubro-1', sort_order: 0,
        name: 'Web Development', description: null, status: 'activo', origin: 'base', overridden_fields: [],
        children: [
          {
            id: 'service-1', base_id: 'base-svc', item_type: 'service', parent_fork_id: 'cat-1', sort_order: 0,
            title: 'Logo Design', description: null, value: 50000, tags: ['frontend'], status: 'desactivado',
            origin: 'override', overridden_fields: ['value'],
          },
        ],
      },
    ],
  },
];

describe('CatalogoPage — tree rendering and badges', () => {
  it('renders the full nested rubro → categoria → service hierarchy', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(detailTree);
    await renderPage();

    expect(screen.getByRole('heading', { name: 'Informática' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Web Development' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Logo Design' })).toBeInTheDocument();
    expect(screen.getByText(/50[.,]000/)).toBeInTheDocument();
    expect(screen.getByText(/frontend/)).toBeInTheDocument();
  });

  it('shows effective status, origin, and per-field override badges', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(detailTree);
    await renderPage();

    expect(screen.getByText('Inactive')).toBeInTheDocument();
    expect(screen.getByText('Override')).toBeInTheDocument();
    expect(screen.getByText('Value override')).toBeInTheDocument();
    expect(screen.getByText('Base')).toBeInTheDocument();
  });
});

describe('CatalogoPage — filter interactions', () => {
  it('re-fetches the tree with the selected status and origin filters', async () => {
    const user = await renderPage();

    expect(getUserCatalogTree).toHaveBeenLastCalledWith({ status: 'all', origin: undefined });

    await user.click(screen.getByLabelText('Filter by status'));
    await user.click(await screen.findByRole('option', { name: 'Active' }));
    await waitFor(() =>
      expect(getUserCatalogTree).toHaveBeenLastCalledWith({ status: 'activo', origin: undefined }),
    );

    await user.click(screen.getByLabelText('Filter by origin'));
    await user.click(await screen.findByRole('option', { name: 'Base' }));
    await waitFor(() =>
      expect(getUserCatalogTree).toHaveBeenLastCalledWith({ status: 'activo', origin: 'base' }),
    );
  });
});

describe('CatalogoPage — rubro creation', () => {
  it('forks a base rubro from the Base Catalog tab and reloads', async () => {
    const user = userEvent.setup();
    render(<CatalogoPage />);

    await screen.findByText('Base Design');
    const callsBefore = jest.mocked(getUserCatalogTree).mock.calls.length;

    await user.click(screen.getByRole('button', { name: 'Select' }));

    await waitFor(() => expect(forkBaseItem).toHaveBeenCalledWith('rubro', 'base-rubro'));
    await waitFor(() =>
      expect(jest.mocked(getUserCatalogTree).mock.calls.length).toBeGreaterThan(callsBefore),
    );
  });

  it('creates a personal rubro from the My Catalog add button', async () => {
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Add rubro' }));

    expect(await screen.findByRole('heading', { name: 'New rubro' })).toBeInTheDocument();

    await user.type(screen.getByLabelText('Name'), 'My Rubro');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(createPersonalItem).toHaveBeenCalledWith('rubro', { name: 'My Rubro', description: '' }),
    );
  });
});

describe('CatalogoPage — reactivation', () => {
  it('reactivates a deactivated node directly without a confirmation dialog', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(detailTree);
    const user = await renderPage();
    const callsBefore = jest.mocked(getUserCatalogTree).mock.calls.length;

    await user.click(screen.getByRole('button', { name: 'Reactivate Logo Design' }));

    await waitFor(() =>
      expect(updatePersonalItemStatus).toHaveBeenCalledWith('service', 'service-1', 'activo'),
    );
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    await waitFor(() =>
      expect(jest.mocked(getUserCatalogTree).mock.calls.length).toBeGreaterThan(callsBefore),
    );
  });
});

describe('CatalogoPage — 409 conflict', () => {
  it('maps a 409 response to a form-level error and keeps the dialog open', async () => {
    const user = await renderPage();
    jest.mocked(createPersonalItem).mockRejectedValueOnce(new ApiError('Duplicate fork identity.', 409));

    await user.click(screen.getByRole('button', { name: 'Add category' }));
    await user.type(screen.getByLabelText('Name'), 'Consulting');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    expect(await screen.findByRole('alert')).toHaveTextContent('Duplicate fork identity.');
    expect(screen.getByRole('heading', { name: 'New category' })).toBeInTheDocument();
  });
});

const revertTree: CatalogNode[] = [
  {
    ...rubroNode,
    children: [
      {
        ...categoriaNode,
        children: [
          {
            id: 'service-1', base_id: 'base-svc', item_type: 'service', parent_fork_id: 'cat-1', sort_order: 0,
            title: 'My Logo', description: 'Brand logo', value: 50000, tags: [], status: 'activo',
            origin: 'override', overridden_fields: ['title'],
          },
        ],
      },
    ],
  },
];

describe('CatalogoPage — revert-to-base', () => {
  it('sends an explicit null for the reverted field through updatePersonalItem', async () => {
    jest.mocked(getUserCatalogTree).mockResolvedValue(revertTree);
    const user = await renderPage();

    await user.click(screen.getByRole('button', { name: 'Edit My Logo' }));
    await user.click(await screen.findByRole('button', { name: 'Revert title to base' }));
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(updatePersonalItem).toHaveBeenCalledWith('service', 'service-1', { title: null }),
    );
  });
});

describe('CatalogoPage — successful child creation', () => {
  it('closes the dialog and reloads the tree after creating a categoria', async () => {
    const user = await renderPage();
    const callsBefore = jest.mocked(getUserCatalogTree).mock.calls.length;

    await user.click(screen.getByRole('button', { name: 'Add category' }));
    await user.type(screen.getByLabelText('Name'), 'Consulting');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() =>
      expect(createPersonalItem).toHaveBeenCalledWith('categoria', {
        name: 'Consulting',
        description: '',
        parent_fork_id: 'rubro-1',
      }),
    );
    await waitFor(() =>
      expect(screen.queryByRole('heading', { name: 'New category' })).not.toBeInTheDocument(),
    );
    await waitFor(() =>
      expect(jest.mocked(getUserCatalogTree).mock.calls.length).toBeGreaterThan(callsBefore),
    );
  });
});
