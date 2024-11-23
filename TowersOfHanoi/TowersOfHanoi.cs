using Spectre.Console;
using TowersOfHanoi.Enums;

namespace TowersOfHanoi;
public class TowersOfHanoi
{
    public int DisksNumber { get; private set; }
    private Tower<Disk>[] _towers = new Tower<Disk>[] { new(), new(), new() };

    public TowersOfHanoi(int disksNumber)
    {
        DisksNumber = disksNumber;
    }

    public void Play()
    {
        InitializeGame();

        while (!IsGameWon())
        {
            RenderGame();
            HandlePlayerMove();
        }

        AnsiConsole.WriteLine("Congratulations, you solved the puzzle!");
        Console.ReadKey();
    }

    private void InitializeGame()
    {
        for (int i = DisksNumber; i >= 1; i--)
        {
            _towers[0].Push(new Disk(i));
        }
    }

    private void HandlePlayerMove()
    {
        var source = AnsiConsole.Prompt(
            new SelectionPrompt<Towers>()
            .Title("Enter the source tower")
            .AddChoices(Enum.GetValues<Towers>())
        );

        var destination = AnsiConsole.Prompt(
            new SelectionPrompt<Towers>()
            .Title("Enter the destination tower")
            .AddChoices(Enum.GetValues<Towers>())
        );

        var sourceTower = GetTower(source);

        var destinationTower = GetTower(destination);

        try
        {
            var disk = sourceTower.Pop();
            destinationTower.Push(disk);
        }
        catch (Exception ex)
        {
            AnsiConsole.WriteLine($"Error: {ex.Message}");
            Console.ReadKey();
        }
    }

    private void RenderGame()
    {
        Console.Clear();
        for (int i = 0; i < _towers.Length; i++)
        {
            Console.Write($"Tower {i + 1}: ");
            _towers[i].Print();
            Console.WriteLine();
        }
    }

    private Tower<Disk> GetTower(Towers tower)
    {
        return tower switch
        {
            Towers.A => _towers[0],
            Towers.B => _towers[1],
            Towers.C => _towers[2]
        };
    }

    private bool IsGameWon() => _towers[2].GetElementsCount() == 3;
}
