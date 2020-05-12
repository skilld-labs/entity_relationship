
# Deprecation notice

This module has been moved to https://www.drupal.org/project/entity_relationship_diagram

Do not use this repo anymore

# Entity relationship diagram
This module generates entity relationship diagram for chosen content entities.
This module was originally ported from Drupal 7 [entitiesdiagram](https://github.com/Gizra/entitiesdiagram) with several improvements.

# Usage
Go to `/admin/reports/entity-relationship-diagram` to see form with available content entity types.
Mark necessary for you entity types and press on create button.

Generates a graph in the PNG format.

![entity_relations___productivity](https://cloud.githubusercontent.com/assets/165644/12092755/ad4bb60e-b307-11e5-904f-a75ee8db7b5c.png)
![entity_relations___productivity](https://cloud.githubusercontent.com/assets/165644/12093435/8a52dd54-b30b-11e5-9b43-2f63e5befd66.png)

Optionally, if you install CLI package "graphviz" (Alpine Linux users could also need package "ttf-freefont"), you can to output diagram
as an image file, by executing following drush command:

drush entity_relationship_diagram:diagram | dot -Gratio=0.7 -Eminlen=2 -T png -o ./output.png

It is also possible to specify desired entity types:

drush entity_relationship_diagram:diagram --entity_type=node,user,taxonomy_term | dot -Gratio=0.7 -Eminlen=2 -T png -o ./output.png
