# API documentation

This page will document the API classes and ways to properly use the API.

## Generalities
* All API methods that use parameters must receive an associative array.
* All parameters accepting a date and time can receive a [DateTime](https://www.php.net/manual/en/class.datetime.php) object indicating a timezone.

## Lists API

### Create a contact list
* [API documentation](https://doc.maildrop.fr/list-create-t127.html)
* [Show me an example](/examples/list-create.php)

```php
$maildrop->lists()->create([
    "name" => "My contacts list"
]);
```

### Retrieve a collection of contact list
* [API documentation](https://doc.maildrop.fr/list-get-t141.html)
* [Show me an example](/examples/list-get.php)

```php
$maildrop->lists()->get([
    "sort_field" => "name",
    "sort_dir" => "asc"
]);
```

### Delete a contact list
* [API documentation](https://doc.maildrop.fr/list-delete-t129.html)
* [Show me an example](/examples/list-delete.php)

```php
$maildrop->lists()->delete([
    "listid" => "XXXXXXXXXXXXXXXXXXX"
]);
```

## Custom fields API
### Add a custom field to a contact list
* [API documentation](https://doc.maildrop.fr/list-addcustomfield-t130.html)
* [Show me an example](/examples/list-add-customfield.php)

```php
$maildrop->custom_fields()->add();
```


## Segments API
### Add a segment to a contact list
* [API documentation](https://doc.maildrop.fr/list-addsegment-t131.html)
* [Show me an example](/examples/list-add-segment.php)

```php
$maildrop->segments()->add();
```

## Campaigns API
### Retrieve a collection of campaigns
* [API documentation](https://doc.maildrop.fr/campaign-get-t142.html)
* [Show me an example](/examples/campaigns-get.php)

```php
$maildrop->campaigns()->get();
```
### Create an email campaign
* [API documentation](https://doc.maildrop.fr/campaign-create-t121.html)
* [Show me an example](/examples/campaign-create.php)

```php
$maildrop->campaigns()->create();
```

### Schedule an email campaign for later sending
* [API documentation](https://doc.maildrop.fr/campaign-schedule-t123.html)
* [Show me an example](/examples/campaign-schedule.php)

```php
$maildrop->campaigns()->schedule();
```

### Schedule an email campaign for immediate sending
* [API documentation](https://doc.maildrop.fr/campaign-send-t122.html)
* [Show me an example](/examples/campaign-send.php)

```php
$maildrop->campaigns()->send();
```

### Delete an email campaign
* [API documentation](https://doc.maildrop.fr/campaign-delete-t154.html)
* [Show me an example](/examples/campaign-delete.php)

```php
$maildrop->campaigns()->delete();
```

## Subscribers API
### Add a contact to a contact list
* [API documentation](https://doc.maildrop.fr/subscriber-add-t61.html)
* [Show me an example](/examples/subscribe-to-list.php)

```php
$maildrop->subscribers()->add();
```

### Add a contact to a contact list and force resubscribe it
* [API documentation](https://doc.maildrop.fr/subscriber-addandresubscribe-t64.html)
* [Show me an example](/examples/subscribe-to-list-and-force-resubscribe.php)

```php
$maildrop->subscribers()->add_and_resubscribe();
```

### Add many contacts to a contact list
* [API documentation](https://doc.maildrop.fr/subscriber-importbatch-t133.html)
* [Show me an example](/examples/subscribe-batch.php)

```php
$maildrop->subscribers()->import_batch();
```

### Asynchronously import contacts via a CSV file
* [API documentation](https://doc.maildrop.fr/subscriber-importbyurl-t137.html)
* [Show me an example](/examples/subscribe-import-csv-by-url.php)

```php
$maildrop->subscribers()->import_by_url();
```

### Check the status of an asynchronously import
* [API documentation](https://doc.maildrop.fr/subscriber-importbyurlgetstatus-t138.html)
* [Show me an example](/examples/subscribe-get-task-status.php)

```php
$maildrop->subscribers()->import_by_url_get_status();
```

### Unsubscribe one or many contacts from a list
* [API documentation](https://doc.maildrop.fr/subscriber-unsubscribebatch-t139.html)
* [Show me an example](/examples/unsubscribe-from-list.php)

```php
$maildrop->subscribers()->unsubscribe();
```

### Get many details of a contact
* [API documentation](https://doc.maildrop.fr/subscriber-getdetails-t158.html)
* [Show me an example](/examples/subscribe-getdetails.php)

```php
$maildrop->subscribers()->get_details();
```

## Reports API
### Retrieve the recipients
* [API documentation](https://doc.maildrop.fr/reports-recipients-t153.html)
* [Show me an example](/examples/reports-get-recipients.php)

```php
$maildrop->reports()->recipients();
```

### Retrieve the openers
* [API documentation](https://doc.maildrop.fr/reports-opens-t132.html)
* [Show me an example](/examples/reports-get-opens.php)

```php
$maildrop->reports()->opens();
```

### Retrieve the bounces
* [API documentation](https://doc.maildrop.fr/reports-bouncemessages-t128.html)
* [Show me an example](/examples/reports-get-bounces.php)

```php
$maildrop->reports()->bounces();
```

### Retrieve the unsubscribes
* [API documentation](https://doc.maildrop.fr/reports-unsubscribes-t126.html)
* [Show me an example](/examples/reports-get-unsubscribes.php)

```php
$maildrop->reports()->unsubscribes();
```

### Retrieve the clicked URL
* [API documentation](https://doc.maildrop.fr/reports-clicks-t124.html)
* [Show me an example](/examples/reports-get-clicks.php)

```php
$maildrop->reports()->clicks();
```

### Retrieve the clickers of an URL
* [API documentation](https://doc.maildrop.fr/reports-clickdetails-t125.html)
* [Show me an example](/examples/reports-get-clicks-details.php)

```php
$maildrop->reports()->clicks_details();
```

## Ips Pool API
### List pool of dedicated IP on your account

* [API documentation](https://doc.maildrop.fr/ippool-list-t134.html)
* [Show me an example](/examples/ips-of-an-ip-pool.php)

```php
$maildrop->ip_pools()->get();
```

### List IPs of an IP pool

* [API documentation](https://doc.maildrop.fr/ippool-ips-t135.html)
* [Show me an example](/examples/ips-of-an-ip-pool.php)

```php
$maildrop->ip_pools()->ips();
```

## Partner API
### Retrieve informations about your partner account

* [API documentation](https://doc.maildrop.fr/partner-myaccount-t160.html)
* [Show me an example](/examples/partner-myaccount.php)

```php
$maildrop->partner()->my_account();
```

## Client API
### List clients managed by your partner account

* [API documentation](https://doc.maildrop.fr/client-get-t161.html)
* [Show me an example](/examples/get-client-list.php)

```php
$maildrop->clients()->get();
```

### Update a client account

* [API documentation](https://doc.maildrop.fr/client-update-t162.html)
* [Show me an example](/examples/update-a-client.php)

```php
$maildrop->clients()->update();
```

## SendingDomains API
### List sending domains configured on an account

* [API documentation](https://doc.maildrop.fr/sendingdomains-get-t163.html)
* [Show me an example](/examples/sending-domains-get.php)

```php
$maildrop->sending_domains()->get();
```

### Add a sending domain

* [API documentation](https://doc.maildrop.fr/sendingdomains-add-t164.html)
* [Show me an example](/examples/sending-domains-add.php)

```php
$maildrop->sending_domains()->add();
```

### Update a sending domain

* [API documentation](https://doc.maildrop.fr/sendingdomains-update-t165.html)
* [Show me an example](/examples/sending-domains-update.php)

```php
$maildrop->sending_domains()->update();
```

### Verify DKIM setup for a sending domain

* [API documentation](https://doc.maildrop.fr/sendingdomains-verifydkim-t167.html)
* [Show me an example](/examples/sending-domains-verify-dkim.php)

```php
$maildrop->sending_domains()->verify_dkim();
```

### Verify Return-Path setup for a sending domain

* [API documentation](https://doc.maildrop.fr/sendingdomains-verifyreturnpath-t168.html)
* [Show me an example](/examples/sending-domains-verify-returnpath.php)

```php
$maildrop->sending_domains()->verify_returnpath();
```

### Delete a configured sending domain

* [API documentation](https://doc.maildrop.fr/sendingdomains-delete-t166.html)
* [Show me an example](/examples/sending-domains-delete.php)

```php
$maildrop->sending_domains()->delete();
```

## TrackingDomains API
### List tracking and/or storage domains configured on an account

* [API documentation](https://doc.maildrop.fr/trackingdomains-get-t169.html)
* [Show me an example](/examples/tracking-domains-get.php)

```php
$maildrop->tracking_domains()->get();
```

### Add a tracking/storage domain

* [API documentation](https://doc.maildrop.fr/trackingdomains-add-t170.html)
* [Show me an example](/examples/tracking-domains-add.php)

```php
$maildrop->tracking_domains()->add();
```

### Verify the ownership of a tracking/storage domain

* [API documentation](https://doc.maildrop.fr/trackingdomains-verifyownership-t172.html)
* [Show me an example](/examples/tracking-domains-verify-ownership.php)

```php
$maildrop->tracking_domains()->verify_cname();
```

### Verify the CNAME setup for a tracking/storage domain

* [API documentation](https://doc.maildrop.fr/trackingdomains-verifycname-t171.html)
* [Show me an example](/examples/tracking-domains-verify-cname.php)

```php
$maildrop->tracking_domains()->verify_cname();
```

### Delete a configured tracking/storage domain

* [API documentation](https://doc.maildrop.fr/trackingdomains-delete-t173.html)
* [Show me an example](/examples/tracking-domains-delete.php)

```php
$maildrop->sending_domains()->delete();
```

## Transactional API

### Pause transactional email

* [API documentation](https://doc.maildrop.fr/transactional-pause-t174.html)
* [Show me an example](/examples/transactional-pause.php)

```php
$maildrop->transactionals()->pause();
```

### Resume transactional email

* [API documentation](https://doc.maildrop.fr/transactional-resume-t175.html)
* [Show me an example](/examples/transactional-resume.php)

```php
$maildrop->transactionals()->resume();
```
