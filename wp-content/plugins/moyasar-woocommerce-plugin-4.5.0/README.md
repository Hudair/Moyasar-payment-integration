# Moyasar Woocommerce Plugin


## Publish
Make sure you have `zip` utility installed. On macOS can be installed with

```shell
brew install zip
```

Now make `publish` executable by using `chmod`

```shell
chmod +x publish
```

Now make a release ZIP archive:

```shell
# You must be in the same directory as publish
# or you are going to have a bad day

./publish
```

Checkout `dist` directory for the resulting ZIP file.


## Contribution
TBA


## Serving Apple Pay Association File

WordPress must be configured to use the `Permalinks` feature in order to support serving the Apple Pay association file.

You can learn more here: https://wordpress.org/support/article/using-permalinks/

If you prefer the manual way, download the association file and upload it to your server to the `.well-known` directory at the root
of your WordPress website.
