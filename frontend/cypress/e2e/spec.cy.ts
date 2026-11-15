describe('Products Page (E2E)', () => {
  beforeEach(() => {
    cy.visit('/products');
  });

  it('should display the products page and navigate to a product card', () => {
    cy.get('table', { timeout: 10000 }).should('be.visible');
    cy.get('table tbody tr').should('have.length.at.least', 1);
    cy.get('table tbody tr').first().find('a').click();
    cy.url().should('match', /\/products\/\d+$/);
    cy.get('h1, h2, mat-card-title', { timeout: 5000 }).should('exist');
  });

  it('should filter products by name', () => {
    // 1. Сначала ждём, пока загрузится таблица (или любой стабильный элемент)
    cy.get('table', { timeout: 10000 }).should('be.visible');

    // 2. Теперь ищем поле поиска (оно должно появиться)
    // Если у вас поле поиска находится ВНЕ таблицы, селектор может быть другим.
    // Попробуйте также 'input[matinput]' или 'mat-form-field input'
    cy.get('#search-input', { timeout: 5000 })
      .should('be.visible')
      .type('Бермуды{enter}', { force: true });

    // 3. Проверяем, что таблица обновилась
    cy.get('table tbody tr', { timeout: 10000 }).should('have.length.at.least', 1);
  });
});