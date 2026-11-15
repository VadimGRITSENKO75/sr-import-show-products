import { createFeature, createReducer, on } from '@ngrx/store';
import { ProductsActions } from './products.actions';
import { Product } from '../../models/product.model';

export interface ProductsState {
  items: Product[];
  total: number;
  page: number;
  limit: number;
  loading: boolean;
  error: string | null;
}

const initialState: ProductsState = {
  items: [],
  total: 0,
  page: 1,
  limit: 20,
  loading: false,
  error: null,
};

export const productsFeature = createFeature({
  name: 'products',
  reducer: createReducer(
    initialState,
    on(ProductsActions.loadProducts, (state, { page, limit }) => ({
      ...state,
      loading: true,
      page,
      limit,
      error: null,
    })),
    on(ProductsActions.loadProductsSuccess, (state, { items, total }) => ({
      ...state,
      items,
      total,
      loading: false,
    })),
    on(ProductsActions.loadProductsFailure, (state, { error }) => ({
      ...state,
      loading: false,
      error,
    }))
  ),
});

export const {
  selectItems: selectAllProducts,
  selectLoading: selectProductsLoading,
  selectTotal,
  selectPage,
  selectLimit,
} = productsFeature;