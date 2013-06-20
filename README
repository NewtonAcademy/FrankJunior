## FrankJunior README

### What is FrankJunior?

FrankJunior is a lightweight PHP framework with which you can quickly and easily build a database-backed RESTful API.

### Who wrote it?

FrankJunior is written by Binyamin Bauman of [Newton Academy](http://www.newtonacademy.org). It is a stripped-down, modified, and enhanced version of [Frank](https://github.com/brucespang/Frank.php), which in turn is a clone of [Sinatra](http://www.sinatrarb.com/).

### Why was FrankJunior written?

FrankJunior is primarily a learning tool for developers in training. As such, non-essential functionality has been removed, and the remaining code has been heavily commented and refactored with an eye toward simplicity.

### How do I install FrankJunior?

1. Copy all of FrankJunior's files into a directory somewhere within your Apache-driven website's `DocumentRoot`. For the purposes of this `README`, we're going to assume your website is `www.example.com`, and that you've copied FrankJunior's files into `/api`.
2. Create and save your database credentials file outside the website's root, as per the template provided in a comment inside `index.php`. If you don't have an existing database to use with FrankJunior, create a new one; just make sure the database name matches whatever is specified in the credentials file.
3. Make sure that Apache is configured to `AllowOverride` for the `/api` directory, so that the settings in the `.htaccess` file can take effect.
4. Test your installation by visiting `http://www.example.com/api` in a browser. You should see a message that says "Welcome to FrankJunior!".
5. Visit `http://www.example.com/api/colors`, which is the address of the example API included with FrankJunior. The first time you do so, the database table that this API utilizes will be automatically created.

### How does FrankJunior work?

Once you've installed FrankJunior, check out the usage examples in the included `index.php` file. Simply put, you register "routes" with FrankJunior. A route is a request path to match, for example `/whut/else`. You also register a callback function for each route, for example `function(){ echo 'Some output.'; }`. When a request is made to FrankJunior, it finds the route that matches the request and invokes its callback function. Following our example, when the user loads `http://www.example.com/api/whut/else`, they'll see a page that says "Some output."

The routes that you register can include named parameters, wildcards, and regular expressions, which makes FrankJunior really powerful and cool.
