export interface ProductAttribute {
  key: string;
  value: string | null;
}

export interface ProductImage {
  id?: number;
  url: string;
  path: string | null;
}

export interface Product {
  id: number;
  external_code: string;
  name: string;
  description: string | null;
  price: number;
  discount: number | null;
  attributes?: ProductAttribute[];
  images?: ProductImage[];
}

export interface ProductsMeta {
  total: number;
  page: number;
  limit: number;
  total_pages: number;
}

export interface ProductsResponse {
  data: Product[];
  meta: ProductsMeta;
}

export interface ImportJob {
  id: number;
  status: string;
  total_rows: number;
  processed_rows: number;
  success_count: number;
  error_count: number;
  progress_percent: number;
  errors: ImportError[];
  created_at: string;
  finished_at: string | null;
}

export interface ImportError {
  row: number;
  external_code?: string | null;
  message: string;
}

export interface ProductFilters {
  name?: string;
  priceMin?: number | null;
  priceMax?: number | null;
  page?: number;
  limit?: number;
}
