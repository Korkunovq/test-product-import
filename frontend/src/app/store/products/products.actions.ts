import { createAction, props } from '@ngrx/store';
import { Product, ProductFilters } from '../../models/product.model';

export const loadProducts = createAction(
  '[Products] Load Products',
  props<{ filters: ProductFilters }>()
);

export const loadProductsSuccess = createAction(
  '[Products] Load Products Success',
  props<{
    products: Product[];
    total: number;
    page: number;
    limit: number;
    totalPages: number;
  }>()
);

export const loadProductsFailure = createAction(
  '[Products] Load Products Failure',
  props<{ error: string }>()
);
