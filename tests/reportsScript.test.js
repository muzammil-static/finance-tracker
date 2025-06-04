const { expect } = require('chai');
const spies = require('chai-spies');
const sinon = require('sinon');
const jsdom = require('jsdom');
const { JSDOM } = jsdom;

chai.use(spies);

describe('reportsScript.js', function () {
    let dom, window, document, Chart, localStorageMock, addEventListenerSpy, removeEventListenerSpy;

    beforeEach(function () {
        dom = new JSDOM(`<!DOCTYPE html>
            <body>
                <input id="date-range" />
                <div class="filter-btn" data-range="week"></div>
                <div class="filter-btn" data-range="month"></div>
                <input id="transactions-data" value='[{"amount":"100","date":"2023-01-01","category":"Salary","type":"income"},{"amount":"50","date":"2023-01-02","category":"Food","type":"expense"}]' />
                <input id="user-currency" value="$" />
                <canvas id="income-expense-chart"></canvas>
                <canvas id="category-chart"></canvas>
                <canvas id="incomeChart"></canvas>
                <canvas id="expenseChart"></canvas>
                <canvas id="categoryChart"></canvas>
            </body>`);
        window = dom.window;
        document = window.document;

        // Mock Chart constructor
        Chart = chai.spy(function (ctx, config) {
            this.ctx = ctx;
            this.config = config;
            this.destroy = chai.spy();
        });

        // Mock localStorage
        localStorageMock = (() => {
            let store = {};
            return {
                getItem: chai.spy((key) => store[key] || null),
                setItem: chai.spy((key, value) => { store[key] = value + ''; }),
                removeItem: chai.spy((key) => { delete store[key]; }),
                clear: chai.spy(() => { store = {}; })
            };
        })();

        // Mock global objects
        global.window = window;
        global.document = document;
        global.Chart = Chart;
        global.localStorage = localStorageMock;
        global.flatpickr = chai.spy(() => ({
            selectedDates: [],
            config: {}
        }));

        // Mock fetch for settings and data
        global.fetch = chai.spy((url) => {
            if (url === 'get_settings.php') {
                return Promise.resolve({
                    json: () => Promise.resolve({ theme: 'light', currency_symbol: '$' })
                });
            }
            if (url === 'get_income_data.php' || url === 'get_expense_data.php' || url === 'get_category_data.php') {
                return Promise.resolve({
                    json: () => Promise.resolve({
                        labels: ['Jan', 'Feb'],
                        values: [100, 200]
                    })
                });
            }
            return Promise.reject(new Error('Unknown URL'));
        });

        // Mock applyTheme and loadSettings
        global.applyTheme = chai.spy();
        global.loadSettings = chai.spy(() => Promise.resolve({ theme: 'light', currency_symbol: '$' }));

        // Attach spies to console
        chai.spy.on(console, 'error');
        chai.spy.on(console, 'warn');
    });

    afterEach(function () {
        chai.spy.restore(console, 'error');
        chai.spy.restore(console, 'warn');
        delete global.window;
        delete global.document;
        delete global.Chart;
        delete global.localStorage;
        delete global.flatpickr;
        delete global.fetch;
        delete global.applyTheme;
        delete global.loadSettings;
    });

    it('test_charts_render_with_valid_elements_and_data', async function () {
        // Load the script
        require('../reportsScript.js');

        // Simulate DOMContentLoaded
        const event = new window.Event('DOMContentLoaded');
        document.dispatchEvent(event);

        // Wait for async chart initialization
        await new Promise(resolve => setTimeout(resolve, 10));

        // Check that Chart was called for both charts
        expect(Chart).to.have.been.called();
        // Should have created chart instances for income-expense and category
        expect(document.getElementById('income-expense-chart')).to.exist;
        expect(document.getElementById('category-chart')).to.exist;
    });

    it('test_quick_filter_buttons_update_charts', function () {
        require('../reportsScript.js');
        // Simulate click on the first filter button
        const filterBtn = document.querySelectorAll('.filter-btn')[0];
        filterBtn.dataset.range = 'week';
        filterBtn.click();

        // Chart should be updated (destroy called and new Chart created)
        expect(Chart).to.have.been.called();
    });

    it('test_theme_change_and_persistence', function () {
        require('../reportsScript.js');
        // Simulate theme change event
        const event = new window.CustomEvent('themeChanged', { detail: { theme: 'dark' } });
        window.dispatchEvent(event);

        expect(document.body.classList.contains('dark-mode')).to.be.true;

        // Simulate reload: theme should persist
        localStorageMock.getItem.withArgs('theme').returns('dark');
        document.body.classList.remove('dark-mode', 'light-mode');
        // Call initializeTheme again
        const initializeTheme = window.initializeTheme || require('../reportsScript.js').initializeTheme;
        initializeTheme();
        expect(document.body.classList.contains('dark-mode')).to.be.true;
    });

    it('test_missing_required_dom_elements', function () {
        // Remove required elements
        document.getElementById('date-range').remove();
        document.querySelectorAll('.filter-btn').forEach(el => el.remove());

        require('../reportsScript.js');
        expect(console.error).to.have.been.called.with('Charts.js failed to find required elements. Stopping initialization.');
    });

    it('test_malformed_transaction_data_handling', function () {
        // Set malformed JSON
        document.getElementById('transactions-data').value = 'not a json';
        require('../reportsScript.js');
        expect(console.error).to.have.been.called.withMatch(/Error parsing transactions/);
    });

    it('test_transactions_with_missing_or_invalid_fields', function () {
        // Set transactions with missing/invalid fields
        document.getElementById('transactions-data').value = JSON.stringify([
            { amount: "abc", date: "invalid-date", category: "Food", type: "expense" },
            { amount: undefined, date: "2023-01-01", category: "Salary", type: "income" },
            { date: "2023-01-02", category: "Misc", type: "expense" },
            { amount: "50", category: "Food", type: "expense" }
        ]);
        require('../reportsScript.js');
        // Should warn or error but not throw
        expect(console.warn).to.have.been.called();
        expect(console.error).to.have.been.called();
        // Chart should still be rendered
        expect(Chart).to.have.been.called();
    });
});