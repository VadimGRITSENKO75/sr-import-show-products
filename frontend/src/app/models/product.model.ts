export interface ProductAttribute {
  id: number;
  key: string;
  value: string;
}

export interface ProductImage {
  id: number;
  url: string;
  path: string;
}

export interface Product {
  id: number;
  externalCode: string;
  name: string;
  description?: string;
  price: string;
  discount: string;
  attributes?: ProductAttribute[];
  images?: ProductImage[];
}

export interface ProductsResponse {
  items: Product[];
  total: number;
  page: number;
  limit: number;
}