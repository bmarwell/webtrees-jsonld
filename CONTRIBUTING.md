# CONTRIBUTING

## Development tools

On Ubuntu, install php and docker:
```
sudo snap install docker
sudo apt install php-cli php-curl php-gd php-json php-intl php-mbstring php-xml php-xdebug php-zip
```

As an IDE, you can choose vscode from snap or phpstorm:
```
sudo snap install phpstorm --classic
sudo snap install code --classic
```

## Committing

* Please execute unit and integration tests before committing.
* Also make sure to format the code using phpfmt(?).