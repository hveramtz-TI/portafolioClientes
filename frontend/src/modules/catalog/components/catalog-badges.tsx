import { Badge } from '@/components/ui/badge';
import type { CatalogNode } from '../api';

/** Human-readable label for each field that can carry a per-field override. */
const FIELD_LABELS: Record<string, string> = {
  name: 'Name',
  title: 'Title',
  description: 'Description',
  value: 'Value',
  tags: 'Tags',
};

export function CatalogBadges({ node }: { node: CatalogNode }) {
  return (
    <div className="flex flex-wrap items-center gap-1.5">
      <Badge variant={node.status === 'activo' ? 'secondary' : 'destructive'}>
        {node.status === 'activo' ? 'Active' : 'Inactive'}
      </Badge>
      <Badge variant="outline">
        {node.origin === 'base' ? 'Base' : node.origin === 'override' ? 'Override' : 'Personal'}
      </Badge>
      {node.overridden_fields.map((field) => (
        <Badge key={field} variant="outline" className="border-dashed">
          {FIELD_LABELS[field] ?? field} override
        </Badge>
      ))}
    </div>
  );
}
