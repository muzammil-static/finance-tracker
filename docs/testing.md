# 🧪 Phase 4: Testing

## 🎯 Objective

To ensure the reliability of core application functions by generating and executing automated test cases using AI-based test generation.

---

## 🧠 AI Tool Used

- **Tool Name**: Qodo Gen (VS Code Extension)
- **Purpose**: Automatically generate JavaScript unit test cases based on function definitions.
- **Framework**: Jest

---

## ⚙️ Process Followed

1. Installed and activated **Qodo Gen** in Visual Studio Code.
2. Identified key functions for testing:
   - `renderBudgetTableFromJSON(budgets, currency)` from `budgScript.js`
   - `initializeIncomeChart(currencySymbol)` from `reportsScript.js`
3. Used Qodo Gen to auto-generate appropriate test cases.
4. Saved the tests in the `/tests/` directory:
   - `budgScript.test.js`
   - `reportsScript.test.js`
5. Reviewed and ran the test cases locally.
6. Took screenshots of both the Qodo Gen interface and the generated test files.
**[Figure 8 – Qodo Gen Interface in VS Code](../docs/screenshots/Figure-8-Qodo-Gen-Interface-in-VS-Code.png)**
**[Figure 9 – Generated test case: renderBudgetTableFromJSON()](../docs/screenshots/Figure-9-Generated-test-cases.png)**
**[Figure 10 – Generated test case: initializeIncomeChart()](../docs/screenshots/Figure-10-Generated-test-cases.png)**
---

## ✅ Test Snippets

```js
// budgScript.test.js
const { expect } = require('chai');
const spies = require('chai-spies');
const sinon = require('sinon');
const { JSDOM } = require('jsdom');
chai.use(spies);

// Import the function to test
const { renderBudgetTableFromJSON } = require('../budgScript.js');

describe('renderBudgetTableFromJSON', function() {
    let dom, document, budgetList, originalGetElementById, originalCreateElement, originalEscapeHtml;

    beforeEach(function() {
        dom = new JSDOM(`<!DOCTYPE html><body><table><tbody id="budget-list"></tbody></table></body>`);
        document = dom.window.document;
        global.document = document;
        budgetList = document.getElementById('budget-list');
        // Mock document.getElementById
        originalGetElementById = document.getElementById;
        chai.spy.on(document, 'getElementById', function(id) {
            if (id === 'budget-list') return budgetList;
            return originalGetElementById.call(document, id);
        });
        // Mock document.createElement
        originalCreateElement = document.createElement;
        chai.spy.on(document, 'createElement', function(tag) {
            return originalCreateElement.call(document, tag);
        });
        // Mock escapeHtml
        originalEscapeHtml = global.escapeHtml;
        global.escapeHtml = chai.spy(function(str) {
            // Simple escape for test
            return str.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        });
        // Mock parseFloat to use the real one
        global.parseFloat = parseFloat;
        // Mock Math.min, Math.max if needed (use real)
        global.Math = Math;
    });

    afterEach(function() {
        chai.spy.restore(document, 'getElementById');
        chai.spy.restore(document, 'createElement');
        if (originalEscapeHtml) {
            global.escapeHtml = originalEscapeHtml;
        } else {
            delete global.escapeHtml;
        }
        delete global.document;
    });

    it('test_renders_table_rows_for_each_budget', function() {
        const budgets = [
            { budget_id: 1, category: 'Food', amount: '100', spent: '50' },
            { budget_id: 2, category: 'Transport', amount: '200', spent: '100' }
        ];
        renderBudgetTableFromJSON(budgets, '$');
        const rows = budgetList.querySelectorAll('tr');
        expect(rows.length).to.equal(2);
        expect(rows[0].innerHTML).to.include('Food');
        expect(rows[1].innerHTML).to.include('Transport');
    });

    it('test_displays_formatted_currency_values', function() {
        const budgets = [
            { budget_id: 1, category: 'Utilities', amount: '123.456', spent: '23.4' }
        ];
        renderBudgetTableFromJSON(budgets, '€');
        const row = budgetList.querySelector('tr');
        const cells = row.querySelectorAll('td.currency-display');
        expect(cells[0].textContent).to.equal('€123.46');
        expect(cells[1].textContent).to.equal('€23.40');
        expect(cells[2].textContent).to.equal('€100.06');
    });

    it('test_applies_correct_progress_bar_color', function() {
        const budgets = [
            { budget_id: 1, category: 'A', amount: '100', spent: '95' },   // 95% -> red
            { budget_id: 2, category: 'B', amount: '100', spent: '75' },   // 75% -> orange
            { budget_id: 3, category: 'C', amount: '100', spent: '50' }    // 50% -> green
        ];
        renderBudgetTableFromJSON(budgets, '$');
        const rows = budgetList.querySelectorAll('tr');
        const getProgressColor = row => {
            const div = row.querySelector('.progress');
            const style = div.getAttribute('style');
            const match = style.match(/background-color:\s*([^;]+);/);
            return match ? match[1] : null;
        };
        expect(getProgressColor(rows[0])).to.equal('red');
        expect(getProgressColor(rows[1])).to.equal('orange');
        expect(getProgressColor(rows[2])).to.equal('green');
    });

    it('test_displays_no_budgets_message_when_empty', function() {
        renderBudgetTableFromJSON([], '$');
        expect(budgetList.innerHTML).to.include('No budgets found.');
        expect(budgetList.querySelectorAll('tr').length).to.equal(1);
    });

    it('test_handles_zero_or_negative_amounts', function() {
        const budgets = [
            { budget_id: 1, category: 'Zero', amount: '0', spent: '10' },
            { budget_id: 2, category: 'Negative', amount: '-100', spent: '50' }
        ];
        renderBudgetTableFromJSON(budgets, '$');
        const rows = budgetList.querySelectorAll('tr');
        // Should not throw, and should render rows
        expect(rows.length).to.equal(2);
        // Progress bar width should be 0% or 100% (clamped)
        const getProgressWidth = row => {
            const div = row.querySelector('.progress');
            const style = div.getAttribute('style');
            const match = style.match(/width:\s*([\d.]+)%/);
            return match ? parseFloat(match[1]) : null;
        };
        expect(getProgressWidth(rows[0])).to.be.within(0, 100);
        expect(getProgressWidth(rows[1])).to.be.within(0, 100);
    });

    it('test_escapes_html_in_category_names', function() {
        const budgets = [
            { budget_id: 1, category: '<script>alert(1)</script>', amount: '100', spent: '10' }
        ];
        renderBudgetTableFromJSON(budgets, '$');
        expect(global.escapeHtml).to.have.been.called.with('<script>alert(1)</script>');
        const row = budgetList.querySelector('tr');
        expect(row.innerHTML).to.include('&lt;script&gt;alert(1)&lt;/script&gt;');
        expect(row.innerHTML).to.not.include('<script>');
    });
});
