### Development

1. Run `npm install`
2. Create `.env` file and update the base url as needed. See `.env.example` file.
3. Run `npm run dev` to start development. The browsersync will proxying BASE_URL to localhost:3000

### Build Assets

The `npm run dev` command only builds the assets for the browsersync server. To build the assets for production, run `npm run build`.

It is not necessary to run this command before committing. Just remember to run it whenever you want to check the site on the staging or production server.

### [Tailwind Content Configuration](https://tailwindcss.com/docs/content-configuration)

Tailwind CSS works by scanning all of your HTML, JavaScript components, and any other template files for class names, then generating all of the corresponding CSS for those styles.

Add those files names to the `content` property in the `tailwind.config.js`. 

DO NOT ADD `./*.php` to the `content` property, it will rebuild the assets endlessly. See here for the reason https://tailwindcss.com/docs/content-configuration

### Github Actions

1. To use Github Actions, rename the `.github/workflows/main` file to `.github/workflows/main.yml`
2. Update `DEPLOY_PATH` variable, the value is the server's file path where this code will be deployed, example: `serverpilot@137.184.132.6:/srv/users/serverpilot/apps/kudosnyc-2023/public/wp-content/themes/kudosnyc`
3. Update `secrets.SSH_PRIVATE_KEY` value. This is the private key that can access the Server using SSH without password
4. Update `secrets.REMOTE_HOSTKEY` value. You can find this key on your local machine in the `~/.ssh/known_hosts` file, search for the row that starts with KUDOS staging IP address (137.184.132.6).

Add the secret key in the repo's settings > secret and variables > actions
