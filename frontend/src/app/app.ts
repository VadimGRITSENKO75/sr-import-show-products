import { Component, signal } from '@angular/core';
import { RouterOutlet, RouterLink } from '@angular/router';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink],
  template: `
    <nav>
      <a routerLink="/products">Список товаров</a> |
      <a routerLink="/import">Импорт</a> | 
      <a routerLink="/imports">История импортов</a>
    </nav>
    <router-outlet></router-outlet>
  `,
})
export class App {
  protected readonly title = signal('frontend');
}
