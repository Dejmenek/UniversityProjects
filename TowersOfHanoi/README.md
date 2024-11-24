# 🏗️ Towers of Hanoi 

A **Towers of Hanoi** game implemented in C# using generic classes and interfaces. This project was created as part of a university assignment.  

---

## 📜 Description  

The goal of this project is to simulate the classic Towers of Hanoi game, showcasing the principles of object-oriented programming in C#. The project includes:  
- **Generic classes** with type constraints.  
- Interfaces such as `IPrintable`, defining the `Print()` method.  
- Custom exceptions for handling for game-specific errors. 
- A robust game manager for handling game logic and menu interactions.
- Interactive console menus powered by Spectre.Console 

---

## 🛠️ Features  

### `Tower<T>` Class  
Represents a single tower in the game.  
#### Main Features:  
- **Constructor**: Initializes the tower with a stack of disks.  
- **Methods**:  
  - `Push(T element)` - adds a disk to the top of the tower. Throws a `LargerOnSmallerElementException` if a larger disk is placed on a smaller one.  
  - `Pop()` - removes and returns the top disk from the tower. Throws an `EmptyTowerException` if the tower is empty.
  - `Peek()` - retrieves the top disk without removing it.
  - `Print()` - displays the tower's state in the console.  
- **Helper Methods**:  
  - `IsMoveValid(T element)` - checks whether a move is valid.
  - `IsTowerEmpty()` - checks if the tower is empty.

> The class only accepts types that implement the `IPrintable` interface and support comparison (`IComparable<T>`).  

---

### `Disk` Class  
Represents a single disk in the game.  
#### Main Features:  
- **Constructor**: Takes the disk size.  
- **Implements**:  
  - `IPrintable` - the `Print()` method to display the size of the disk.  
  - Comparison interface (`IComparable<Disk>`) - enables comparison of disk sizes.  

---

### `GameManager` Class  
Orchestrates the game, managing the menu, game logic, and user interactions.  

#### Main Features:  
- **Run()**: Main loop for the application, processing menu selections and starting new games.  
- **StartNewGame()**: Prompts the user to select the number of disks and initializes a new game.  
- **ShowInstructions()**: Displays the game rules and instructions.  
- **ClearScreen()**: Clears the console for a cleaner display.

___

### `TowersOfHanoi` Class  
Implements the logic for the Towers of Hanoi game.  

#### Main Features:  
- **Constructor**: Initializes the game with the specified number of disks.  
- **Methods**:  
  - `InitializeGame()` - sets up the first tower with disks in descending order.  
  - `Play()` - runs the game loop, rendering the game state and handling player moves.  
  - `RenderGame()` - visualizes the current state of all towers.  
  - `HandlePlayerMove()` - prompts the player to choose source and destination towers and moves disks according to game rules.  
  - `GetTower(Towers tower)` - retrieves the corresponding tower based on the player's selection.  
  - `IsGameWon()` - checks if all disks have been moved to the third tower.  

---

## 👾 Game Rules  

1. The game consists of three towers and a set number of disks.  
2. Initially, all disks are stacked on the first tower.  
3. The goal is to move all disks to the third tower, following these rules:  
   - Only one disk can be moved at a time.  
   - A disk can only be placed on an empty tower or on top of a larger disk.  
