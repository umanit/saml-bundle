.PHONY: trivy

trivy:
	@command -v trivy > /dev/null 2>&1 || { echo "Erreur : le binaire « trivy » est introuvable. Consultez https://github.com/aquasecurity/trivy-repo#debianubuntu pour l’installer."; exit 1; }
	@echo "Analyse de sécurité avec Trivy..."
	@trivy fs . --scanners vuln --skip-dirs "vendor,node_modules,var" $(if $(FULL),,--table-mode summary) --disable-telemetry --exit-code 1

##############################
# BEGIN - Analyses statiques #
##############################
.PHONY: full_static_analyze phpstan phpcs phparkitect twigcs rector

full_static_analyze: phpstan phpcs phparkitect twigcs rector

phpstan:
	@echo "Exécution de phpstan..."
	@symfony php vendor/bin/phpstan -n analyze

phpcs:
	@echo "Exécution de phpcs..."
	@symfony php vendor/bin/phpcs

phparkitect:
	@echo "Exécution de PHPArkitect..."
	@symfony php vendor/bin/phparkitect -n check

twigcs:
	@echo "Exécution de TwigCS..."
	@symfony php vendor/bin/twig-cs-fixer lint

rector:
	@echo "Exécution de Rector..."
	@symfony php vendor/bin/rector process --dry-run
############################
# END - Analyses statiques #
############################

###################
# BEGIN - PHPUnit #
###################
.PHONY: phpunit phpunit_watch

phpunit:
	@if [ -n "$(ARGS)" ]; then \
		symfony php vendor/bin/phpunit --filter $(ARGS); \
	else \
		symfony php vendor/bin/phpunit; \
	fi

phpunit_watch:
	symfony php vendor/bin/phpunit-watcher watch
#################
# END - PHPUnit #
#################
