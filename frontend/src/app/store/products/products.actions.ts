import { createActionGroup, props } from '@ngrx/store';
import { Product } from '../../models/product.model';

export const ProductsActions = createActionGroup({
  source: 'Products',
  events: {
    'Load Products': props<{ page: number; limit: number; filters?: { name?: string; price_min?: number; price_max?: number } }>(),
    'Load Products Success': props<{ items: Product[]; total: number; page: number; limit: number }>(),
    'Load Products Failure': props<{ error: string }>(),
  },
});