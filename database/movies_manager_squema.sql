CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE "users" (
  "id" uuid PRIMARY KEY NOT NULL DEFAULT gen_random_uuid(),
  "full_name" varchar(255) NOT NULL,
  "email" varchar(254) UNIQUE NOT NULL,
  "password_hash" varchar(255) NOT NULL,
  "avatar" varchar(500),
  "created_at" timestamp DEFAULT now(),
  "updated_at" timestamp DEFAULT now()
);

CREATE TABLE "movies" (
  "id" uuid PRIMARY KEY NOT NULL DEFAULT gen_random_uuid(),
  "title" varchar(100) NOT NULL,
  "description" text NOT NULL,
  "genre" varchar(80) NOT NULL,
  "released_at" date NOT NULL,
  "image_src" varchar(500),
  "created_at" timestamp DEFAULT now(),
  "updated_at" timestamp DEFAULT now(),
  "user_id" uuid NOT NULL
);

CREATE TABLE "ratings" (
  "id" uuid PRIMARY KEY NOT NULL DEFAULT gen_random_uuid(),
  "rating" int NOT NULL,
  "review" text NOT NULL,
  "created_at" timestamp DEFAULT now(),
  "updated_at" timestamp DEFAULT now(),
  "user_id" uuid NOT NULL,
  "movie_id" uuid NOT NULL
);

CREATE UNIQUE INDEX ON "movies" ("title", "user_id");
CREATE UNIQUE INDEX ON "ratings" ("user_id", "movie_id");

COMMENT ON TABLE "users" IS 'Users who can log in, create and review movies.';
COMMENT ON TABLE "movies" IS 'Movies created by users. Each movie belongs to a single user.';
COMMENT ON TABLE "ratings" IS 'Each rating belongs to a user and a movie. A user can only rate a movie once.';

COMMENT ON COLUMN "ratings"."rating" IS 'Value must be between 1 and 5 (enforced by CHECK constraint).';

ALTER TABLE "movies" ADD FOREIGN KEY ("user_id") REFERENCES "users" ("id");
ALTER TABLE "ratings" ADD FOREIGN KEY ("user_id") REFERENCES "users" ("id");
ALTER TABLE "ratings" ADD FOREIGN KEY ("movie_id") REFERENCES "movies" ("id");

ALTER TABLE "ratings"
ADD CONSTRAINT rating_value_range CHECK ("rating" BETWEEN 1 AND 5);
