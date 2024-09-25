# tide_data_driven_component
Data Driven Component component for Tide distribution

[![CircleCI](https://circleci.com/gh/dpc-sdp/tide_data_driven_component.svg?style=svg&circle-token=fa59f0c56069c182766c7e1e0d56eb9f205210ab)](https://circleci.com/gh/dpc-sdp/tide_data_driven_component)

# CONTENTS OF THIS FILE

* Introduction
* Requirements
* Installation

# INTRODUCTION
The Tide Data Driven Component module provides the Data Driven Component component and related configurations.
This module defines all configuration to provide the paragraph component to Tide Landing Page.

# REQUIREMENTS
* [Tide Core](https://github.com/dpc-sdp/tide_core)
* [Tide Landing Page](https://github.com/dpc-sdp/tide_landing_page)

# INSTALLATION
Include the Tide Data Driven Component module in your composer.json file

```bash
composer require dpc-sdp/tide_data_driven_component
```

## Component Options
This component can take a list of options in a JSON string in the environment
variable `DATA_DRIVEN_OPTIONS`. For example

```
DATA_DRIVEN_OPTIONS = '{"myvic_vicfreewifi":"Vic Free Wifi Map","myvic_family_violence_women_programs":"Prevention of Family Violence Against Women Programs Map"}'
```
