#!/usr/bin/env osascript

tell application "Finder"
    activate
    delay 1
    # set bounds to {0, 46, 2560, 1440} -- {l, t, r, b}
    set current view to icon view
    tell its icon view options
        set icon size to 36
    end tell
end tell
