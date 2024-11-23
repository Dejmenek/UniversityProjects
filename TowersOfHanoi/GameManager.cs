using Spectre.Console;
using TowersOfHanoi.Enums;

namespace TowersOfHanoi;
public class GameManager
{
    private readonly Menu _menu = new Menu();

    public void Run()
    {
        bool exit = false;

        while (!exit)
        {
            _menu.ShowMenu();

            var choice = _menu.GetMenuChoice();

            switch (choice)
            {
                case MenuOptions.StartNewGame:
                    StartNewGame();
                    break;
                case MenuOptions.ViewInstructions:
                    ShowInstructions();
                    break;
                case MenuOptions.Exit:
                    exit = true;
                    break;
            }
            Console.Clear();
        }
    }

    private void StartNewGame()
    {
        Console.Clear();
        int disksNumber = AnsiConsole.Prompt(
                new TextPrompt<int>("How many disks would you like to play with?")
                .Validate(n => n switch
                {
                    < 3 => ValidationResult.Error("Too low"),
                    >= 3 => ValidationResult.Success()
                })
            );
        var game = new TowersOfHanoi(disksNumber);
        game.Play();
    }

    private void ShowInstructions()
    {
        AnsiConsole.Write("The goal of the game is to move all the disks from the first tower to the third tower, following these rules:\n"
            + "1. Only one disk can be moved at a time.\n"
            + "2. A disk can only be placed on an empty tower or on top of a larger disk.\n"
            + "Good luck!\n"
            + "Press any key to return to the main menu."
        );
        Console.ReadKey();
    }
}
