import { Badge } from '@/components/ui/badge';
import type { CatalogNode } from '../api';

export function CatalogBadges({ node }: { node: CatalogNode }) {
  return (
    <div className="flex flex-wrap items-center gap-1.5">
      <Badge variant={node.status === 'activo' ? 'secondary' : 'destructive'}>
        {node.status === 'activo' ? 'Active' : 'Inactive'}
      </Badge>
      <Badge variant="outline">
        {node.origin === 'base' ? 'Base' : node.origin === 'override' ? 'Override' : 'Personal'}
      </Badge>
    </div>
  );
}
