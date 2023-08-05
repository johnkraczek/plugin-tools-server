/**
 * @param {import('@roots/bud').Bud} bud
 */
export default (bud) => {
  bud.hash()
  bud.alias(`@client`, bud.path(`@src`, `client`))
  //bud.entry(`client`, `@client/index.js`)
  bud.entry(`client`, [`@client/index.js`, `@client/styles/client.css`])

  bud.devtool()

  //bud.hash(true)
  // bud.store.set('hashFormat', '[name].[contenthash:6]')
};
