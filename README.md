PayPalIpnValidator
============

Use this class in your PayPal IPN listener to validate a request sent by PayPal. The class requires the CURL 
extension. You can always pass callback functions using 'set_callback()' for the various PayPal 'payment_status' 
values that could be received.

Callbacks from any 'payment_status' are allowed...here are some typical ones:

* 'Completed'
* 'Pending'
* 'Refund'
* 'Reversal'
* 'VERIFIED' - this will run before 'Completed', 'Pending', etc.
* 'INVALID'
* 'MISMATCH' - this is not a PayPal status, this will be called if data sent to the 'validate()' method does not match what is in the $_POST array

Check PayPal's developer site (www.x.com/developers/paypal) for details.