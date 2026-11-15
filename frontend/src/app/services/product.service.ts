import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Product, ProductsResponse } from '../models/product.model';

@Injectable({ providedIn: 'root' })
export class ProductService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8080/api/products';

  getProducts(
    page: number,
    limit: number,
    filters?: { name?: string; price_min?: number; price_max?: number }
  ): Observable<ProductsResponse> {
    let params = new HttpParams().set('page', page).set('limit', limit);
    if (filters?.name) params = params.set('name', filters.name);
    if (filters?.price_min != null) params = params.set('price_min', filters.price_min);
    if (filters?.price_max != null) params = params.set('price_max', filters.price_max);
    return this.http.get<ProductsResponse>(this.apiUrl, { params });
  }

  getProduct(id: number): Observable<Product> {
    return this.http.get<Product>(`${this.apiUrl}/${id}`);
  }
}