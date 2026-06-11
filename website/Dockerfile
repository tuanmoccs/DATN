# Build stage
FROM node:20-alpine AS build-stage
WORKDIR /app
ARG VITE_API_ENDPOINT
ARG VITE_STORAGE_ENDPOINT
ENV VITE_API_ENDPOINT=$VITE_API_ENDPOINT
ENV VITE_STORAGE_ENDPOINT=$VITE_STORAGE_ENDPOINT
COPY package*.json ./
RUN npm install
COPY . .
# Build the application
RUN npm run build

# Production stage
FROM nginx:stable-alpine AS production-stage
COPY --from=build-stage /app/dist /usr/share/nginx/html
# Copy standard nginx config for single page applications
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
