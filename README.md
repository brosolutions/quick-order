# QuickOrder for Magento 2

QuickOrder is a free Magento 2 module that allows customers to quickly add multiple products to cart using SKU or product name — all in one simple form.

Built by [BroSolutions](https://www.brosolutions.net), Magento experts since 2015.

---

## Features

### Quick Order Form
- Add multiple products to cart at once
- Search by SKU or product name
- Simple, fast UI
- Supports Simple, Configurable, Grouped, and Bundle products
- Compatible with Hyvä Theme and standard Luma
- Useful for B2B and wholesale stores

### Product Lists
- **Save for later:** Customers can save their Quick Order selections as reusable "Product Lists".
- **Manage:** View, rename, clone, or delete lists from the Customer Account.
- **One-click checkout:** Add an entire saved list to the cart instantly.

### Automated Order Scheduling
- **Recurring Orders:** Convert any saved Product List into an automated schedule.
- **Flexible Frequencies:** Set schedules to run weekly, monthly, or every N months.
- **Smart Validation Rules & Price Lock:** Customers can define how the system should react to price changes, out-of-stock items, or disabled products. The "Price Lock" feature allows customers to set a threshold—the schedule will safely pause or throw an error *only* if the price increases beyond the threshold, allowing them to automatically benefit from price drops.
- **Price Alerts & History:** The system independently monitors active schedules for price changes. If prices change, customers receive an email notification detailing the old and new prices.
- **Interactive Price Chart:** Customers can view a visual price history chart for their scheduled products directly from their account dashboard.
- **Email Notifications:** Keeps customers informed with automatic success, error, and price change emails.
- **Execution History:** Detailed execution logs are available for both customers and admins to track every automated order attempt.
- **Customer Dashboard:** Customers can view timelines, check logs, pause, activate, or delete schedules directly from their account.
- **Admin Control:** Store admins can view and manage all active schedules and their execution history from the backend.

📌 CSV import/export is coming soon.

---

## Installation

Install via Composer:

```bash
composer require brosolutions/quick-order
bin/magento module:enable BroSolutions_QuickOrder
bin/magento setup:upgrade
```

*(Note: Depending on your environment, you may also need to run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy -f` after installation).*

---

## Configuration

To configure the module, navigate to **Stores > Configuration > Bro Solutions > Quick Order** in the Magento Admin Panel:
- **General Settings:** Enable/disable the Quick Order form and set the search results limit.
- **Scheduled & automated orders:** - Enable the scheduling feature.
    - Configure allowed offline payment methods (e.g., Check/Money Order) and shipping methods (e.g., Flat Rate).
    - Toggle the strict "Check Previous Order Status" rule to prevent new orders if previous ones are unpaid.
    - **Enable Price Alerts Cron:** Toggle the independent price check feature on/off and define its cron expression (default is every hour).

---

## Cron Jobs

The automated order scheduling and price alert features rely on Magento's cron system to generate orders and monitor prices in the background. Please ensure your Magento cron is properly configured and running.
- **Order Generation Job:** `brosolutions_quickorder_process_schedules`
    - **Schedule:** Every minute (`* * * * *`)
- **Price Alerts Job:** `brosolutions_quickorder_price_alert`
    - **Schedule:** Configurable in Admin (Default: `0 * * * *` - Every hour)

---

## Usage

After installation, the QuickOrder form will be available at:

```text
/quickorder
```

You can add a link to this page in the menu or place it anywhere on the storefront.

**Managing Lists & Schedules:**
- Customers can access **"My lists"** and **"Scheduled automated orders"** from their account navigation menu. They can also view price history charts directly from their list items.
- Admins can manage automated schedules under **Sales > Automated Orders**.

---

## Feedback & Contributions

Feel free to open issues or submit pull requests.

Need customizations or help with your Magento store?  
[Contact BroSolutions](mailto:contact@brosolutions.net) or visit [brosolutions.net](https://www.brosolutions.net)

---

## License

This module is open-source and free to use under the MIT license.
