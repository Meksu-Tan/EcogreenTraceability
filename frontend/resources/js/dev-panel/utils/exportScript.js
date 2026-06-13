/**
 * Generate Playwright (TypeScript) or Cypress script from recorded steps
 */

function stepToPlaywright(step) {
  const sel = step.selector || 'body'
  switch (step.type) {
    case 'navigate':
      return `  await page.goto('${step.value || '/'}');`
    case 'click':
      return `  await page.locator('${sel}').click();`
    case 'fill':
      return `  await page.locator('${sel}').fill('${(step.value || '').replace(/'/g, "\\'")}');`
    case 'select':
      return `  await page.locator('${sel}').selectOption('${step.value}');`
    case 'check':
      return step.value
        ? `  await page.locator('${sel}').check();`
        : `  await page.locator('${sel}').uncheck();`
    case 'submit':
      return `  await page.locator('${sel}').evaluate(el => el.submit());`
    default:
      return `  // Unknown step type: ${step.type}`
  }
}

function stepToCypress(step) {
  const sel = step.selector || 'body'
  switch (step.type) {
    case 'navigate':
      return `    cy.visit('${step.value || '/'}');`
    case 'click':
      return `    cy.get('${sel}').click();`
    case 'fill':
      return `    cy.get('${sel}').clear().type('${(step.value || '').replace(/'/g, "\\'")}');`
    case 'select':
      return `    cy.get('${sel}').select('${step.value}');`
    case 'check':
      return step.value
        ? `    cy.get('${sel}').check();`
        : `    cy.get('${sel}').uncheck();`
    case 'submit':
      return `    cy.get('${sel}').submit();`
    default:
      return `    // Unknown step type: ${step.type}`
  }
}

export function generatePlaywright(name, steps) {
  const lines = steps.map(stepToPlaywright).join('\n')
  return `import { test, expect } from '@playwright/test';

test('${name}', async ({ page }) => {
${lines}
});
`
}

export function generateCypress(name, steps) {
  const lines = steps.map(stepToCypress).join('\n')
  return `describe('${name}', () => {
  it('should work', () => {
${lines}
  });
});
`
}
