import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Product, ProductFilters, ProductsResponse } from '../models/product.model';

@Injectable({ providedIn: 'root' })
export class ProductService {
  constructor(private readonly http: HttpClient) {}

  getProducts(filters: ProductFilters): Observable<ProductsResponse> {
    let params = new HttpParams()
      .set('page', String(filters.page ?? 1))
      .set('limit', String(filters.limit ?? 20));

    if (filters.name) {
      params = params.set('name', filters.name);
    }
    if (filters.priceMin != null) {
      params = params.set('price_min', String(filters.priceMin));
    }
    if (filters.priceMax != null) {
      params = params.set('price_max', String(filters.priceMax));
    }

    return this.http.get<ProductsResponse>(`${environment.apiUrl}/products`, { params });
  }

  getProduct(id: number): Observable<Product> {
    return this.http.get<Product>(`${environment.apiUrl}/products/${id}`);
  }
}
