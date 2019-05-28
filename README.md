# Using the Jeeb payment plugin for WHMCS

## Prerequisites

You must have a Jeeb merchant account to use this plugin.  It's free and easy to [sign-up for a Jeeb merchant account](https://jeeb.io/home).



## Installation

Extract these files into the WHMCS directory on your webserver (parent directory of
modules/folder).


## Configuration

1. Take a moment to ensure that you have set your store's domain and the WHMCS System URL under **whmcs/admin > Setup > General Settings**.
2. Get the signature from your Jeeb merchant account.
3. In the admin control panel, go to **Setup > Payment Gateways**, select **Jeeb** in the list of modules and click **Activate**.
  * If you can't find the Jeeb plugin in the list of payment gateways -or- in the WHMCS app store, then you may clone this repo and copy modules/gateways into your <whmcs root>/modules/gateways/.
4. Paste the API Key ID string that you created and copied from step 2.
5. Choose which environment you want (Live/Test).
6. Set a Base currency(it usually should be the currency of your store) and Target Currency(It is a multi-select option. You can choose any cryptocurrency from the listed options.).
7. Set the language of the payment page (you can set Auto-Select to auto detecting manner).
8. Click **Save Changes**.

You're done!


## Usage

When a client chooses the Jeeb payment method, they will be presented with an invoice showing a button they will have to click on in order to pay their order.  Upon requesting to pay their order, the system takes the client to a full-screen jeeb.io invoice page where the client is presented with payment instructions.  Once payment is received, a link is presented to the shopper that will return them to your website.

**NOTE:** Don't worry!  A payment will automatically update your WHMCS store whether or not the customer returns to your website after they've paid the invoice.

In your WHMCS control panel, you can see the information associated with each order made via Jeeb by choosing **Orders > Pending Orders**.  This screen will tell you whether payment has been received by the Jeeb servers.
