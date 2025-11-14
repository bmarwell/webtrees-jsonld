BUILD_DIR=build
LANGUAGE_DIR=src/jsonld/language
LANGUAGE_SRC=$(shell git grep -I --name-only --fixed-strings -e I18N:: -- "*.php" "*.xml")
MO_FILES=$(patsubst %.po,%.mo,$(PO_FILES))
PO_FILES=$(wildcard $(LANGUAGE_DIR)/*.po)
SHELL=bash
MKDIR=mkdir -p

.PHONY: clean update vendor build/jsonld test unittest integrationtest-testcontainers dist

all: init src/jsonld/language/messages.pot update test build/jsonld.tar.bz2

clean:
	rm -Rf build/* src/jsonld/language/messages.pot
	rm -Rf build/
	rm -Rf vendor/
	rm -Rf ./tests/integration/nginx-webtrees/scripts

init: vendor

test: init
	php vendor/bin/phpunit

unittest: init
	php vendor/bin/phpunit --testsuite="Unit Tests"

integrationtest-testcontainers: init
	composer test:integration

integrationtest-pre:
	cp -r ./tests/integration/scripts ./tests/integration/nginx-webtrees/
	docker-compose --project-name "webtrees-integration" --file tests/integration/docker-compose.yml build --no-cache
	docker-compose --project-name "webtrees-integration" --file tests/integration/docker-compose.yml up --detach --force-recreate
	sleep 10 && docker-compose --project-name "webtrees-integration" --file tests/integration/docker-compose.yml exec web bash /scripts/setup.sh

integrationtest: init update build/jsonld integrationtest-pre
	./tests/integration/run.sh || echo "see error."
	#docker-compose --project-name "webtrees-integration" --file tests/integration/docker-compose.yml down

update: src/jsonld/language/messages.pot
	composer compile-po

vendor:
	php composer.phar self-update
	php composer.phar install
	php composer.phar dump-autoload --optimize

dist: init
	composer dist

build/jsonld: dist

build/jsonld.tar.bz2: build/jsonld
	tar cvjf $@ $^

src/jsonld/language/messages.pot: $(LANGUAGE_SRC)
	echo $^ | xargs xgettext --package-name="webtrees-jsonld" --package-version=2.0 --msgid-bugs-address=bmarwell+webtrees@gmail.com --no-wrap --language=PHP --add-comments=I18N --from-code=utf-8 --keyword=translate:1 --keyword=translateContext:1c,2 --keyword=plural:1,2 --output=$@

$(PO_FILES): src/jsonld/language/messages.pot
	msgmerge --no-wrap --sort-output --no-fuzzy-matching --output=$@ $@ $<

%.mo: %.po
	msgfmt --output=$@ $<


# vim:noexpandtab
