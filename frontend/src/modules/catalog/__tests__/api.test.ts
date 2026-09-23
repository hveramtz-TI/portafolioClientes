import { createPersonalItem, forkBaseItem, getUserCatalogTree, updatePersonalItem } from '../api';

jest.mock('../../../lib/api', () => ({
  apiFetch: jest.fn(),
}));

import { apiFetch } from '../../../lib/api';

describe('catalog API', () => {
  beforeEach(() => jest.clearAllMocks());

  it('serializes effective tree filters', async () => {
    (apiFetch as jest.Mock).mockResolvedValue([]);

    await getUserCatalogTree({ status: 'desactivado', origin: 'override' });

    expect(apiFetch).toHaveBeenCalledWith('/api/user-catalog/tree?status=desactivado&origin=override');
  });

  it('forks through the user catalog cascade endpoint', async () => {
    (apiFetch as jest.Mock).mockResolvedValue({ id: 'fork' });

    await forkBaseItem('rubro', 'base-id');

    expect(apiFetch).toHaveBeenCalledWith('/api/user-catalog/rubros/base-id/fork', { method: 'POST' });
  });

  describe('Slice 7 contract extensions', () => {
    it('sends parent_fork_id when updating a service (move/attach)', async () => {
      (apiFetch as jest.Mock).mockResolvedValue({ id: 'service-1' });

      await updatePersonalItem('service', 'service-1', { parent_fork_id: 'cat-1' });

      expect(apiFetch).toHaveBeenCalledWith('/api/user-catalog/services/service-1', {
        method: 'PUT',
        body: JSON.stringify({ parent_fork_id: 'cat-1' }),
      });
    });

    it('serializes an explicit null to clear an override (revert-to-base)', async () => {
      (apiFetch as jest.Mock).mockResolvedValue({ id: 'service-1' });

      await updatePersonalItem('service', 'service-1', { title: null });

      expect(apiFetch).toHaveBeenCalledWith('/api/user-catalog/services/service-1', {
        method: 'PUT',
        body: '{"title":null}',
      });
    });

    it('sends parent_fork_id when creating a categoria under a rubro', async () => {
      (apiFetch as jest.Mock).mockResolvedValue({ id: 'cat-1' });

      await createPersonalItem('categoria', { name: 'Cat', parent_fork_id: 'rubro-1' });

      expect(apiFetch).toHaveBeenCalledWith('/api/user-catalog/categorias', {
        method: 'POST',
        body: JSON.stringify({
          item_type: 'categoria',
          name: 'Cat',
          parent_fork_id: 'rubro-1',
        }),
      });
    });

    it('serializes explicit nulls for other override fields on the rubro route', async () => {
      (apiFetch as jest.Mock).mockResolvedValue({ id: 'rubro-1' });

      await updatePersonalItem('rubro', 'rubro-1', { name: null, description: null });

      expect(apiFetch).toHaveBeenCalledWith('/api/user-catalog/rubros/rubro-1', {
        method: 'PUT',
        body: '{"name":null,"description":null}',
      });
    });
  });
});
