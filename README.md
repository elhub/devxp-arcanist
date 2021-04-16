# dev-tools-arcanist

<!-- PROJECT SHIELDS -->
![TeamCity Build](https://teamcity.elhub.cloud/app/rest/builds/buildType:(id:Tools_DevToolsArcanist_AutoRelease)/statusIcon)
[![Quality Gate Status](https://sonar.elhub.cloud/api/project_badges/measure?project=no.elhub.tools%3Adev-tools-arcanist&metric=alert_status)](https://sonar.elhub.cloud/dashboard?id=no.elhub.tools%3Adev-tools-arcanist)
[![Lines of Code](https://sonar.elhub.cloud/api/project_badges/measure?project=no.elhub.tools%3Adev-tools-arcanist&metric=ncloc)](https://sonar.elhub.cloud/dashboard?id=no.elhub.tools%3Adev-tools-arcanist)

[![Vulnerabilities](https://sonar.elhub.cloud/api/project_badges/measure?project=no.elhub.tools%3Adev-tools-arcanist&metric=vulnerabilities)](https://sonar.elhub.cloud/dashboard?id=no.elhub.tools%3Adev-tools-arcanist)
[![Bugs](https://sonar.elhub.cloud/api/project_badges/measure?project=no.elhub.tools%3Adev-tools-arcanist&metric=bugs)](https://sonar.elhub.cloud/dashboard?id=no.elhub.tools%3Adev-tools-arcanist)
[![Code Smells](https://sonar.elhub.cloud/api/project_badges/measure?project=no.elhub.tools%3Adev-tools-arcanist&metric=code_smells)](https://sonar.elhub.cloud/dashboard?id=no.elhub.tools%3Adev-tools-arcanist)


## Table of Contents

* [About](#about)
* [Getting Started](#getting-started)
  * [Prerequisites](#prerequisites)
  * [Installation](#installation)
* [Usage](#usage)
* [Testing](#testing)
* [Roadmap](#roadmap)
* [Contributing](#contributing)
* [Owners](#owners)
* [License](#license)


## About

**dev-tools-arcanist** provides some useful extensions to arcanist used at Elhub.

## Getting Started

### Prerequisites

* [arcanist](https://github.com/phacility/arcanist)

### Installation

Elhub employees should install **dev-tools-arcanist** using the [dev-tools](https://github.com/elhub/dev-tools)
installation suite.

If you cannot use that, you need to add this repository to your local machine and tell Arcanist to load the extension.
This can be done either globally or on a per-project basis. See the
[Arcanist User Guide](https://secure.phabricator.com/book/phabricator/article/arcanist/) for more details.

## Usage

Once installed, the project will make a number of additional lint and unit test engines available in arcanist. The
new linters are:

* Ansible Lint (for ansible/yaml files)
* Checkstyle (for Java)
* Detekt (for Kotlin)
* EsLint (for JS)
* Prettier (for JS)
* Terraform Fmt (for Terraform)
* Yaml Lint (for yaml files)

In addition, it provides a unit test engine for:

* Gradle
* Jest (NPM)
* Maven

## Testing

N/A.

## Roadmap

See the [open issues](https://jira.elhub.cloud/issues/?jql=project%20%3D%20TD%20AND%20component%20%3D%20dev-tools-arcanist%20AND%20resolution%20%3D%20Unresolved) for a list of proposed features (and known issues).

## Contributing

Contributing, issues and feature requests are welcome. See the
[Contributing](https://github.com/elhub/dev-tools-arcanist/blob/main/CONTRIBUTING.md) file.

## Owners

This project is developed by [Elhub](https://elhub.no). For the specific development group responsible for this
code, see the [Codeowners](https://github.com/elhub/dev-tools-arcanist/blob/main/CODEOWNERS) file.

## License

This project is [MIT](https://github.com/elhub/dev-tools-arcanist/blob/main/LICENSE.md) licensed.
