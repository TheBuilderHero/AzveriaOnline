FROM mysql:8.4

# Bake DB init scripts into the image so compose works across local/remote contexts
COPY database/init/ /docker-entrypoint-initdb.d/
