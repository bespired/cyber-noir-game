
console.log('Hello from Electron 👋')

const { app, BrowserWindow } = require('electron')

const createWindow = () => {
  const win = new BrowserWindow({
    width: 800,
    height: 600
  })

  // In production/simulated mode, we load the built file
  // Make sure to run 'npm run build' before 'npm start'
  win.loadFile('dist/index.html')
}

app.whenReady().then(() => {
  createWindow()
})
