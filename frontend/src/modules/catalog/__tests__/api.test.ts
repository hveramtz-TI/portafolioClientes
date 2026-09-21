import { forkBaseItem, getUserCatalogTree } from '../api';

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
});
