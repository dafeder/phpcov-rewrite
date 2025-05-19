<?php

namespace Dafeder\PhpcovRewrite\Command;

use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RewriteCommand extends Command
{
    protected function configure()
    {
        $this->setName('phpcov-rewrite')
            ->setDescription('Rewrite a phpcov file\'s paths.')
            ->setHelp('Stay tuned...')
            ->addArgument('input', InputArgument::REQUIRED, 'Input file')
            ->addOption('old-prefix', 'f', InputOption::VALUE_REQUIRED, "Existing path prefix")
            ->addOption('new-prefix', 'r', InputOption::VALUE_REQUIRED, "New path prefix");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputFile = $input->getArgument('input');
        $oldPrefix = $input->getOption('old-prefix');
        $newPrefix = $input->getOption('new-prefix');

        if (!file_exists($inputFile)) {
            throw new \RuntimeException("Input file does not exist: $inputFile");
        }

        if ($oldPrefix === null || $newPrefix === null) {
            throw new \RuntimeException("Both old and new prefixes must be provided.");
        }

        // Read the input file
        /** @var \SebastianBergmann\CodeCoverage\CodeCoverage $coverage */
        $coverage = include $inputFile;
        if ($coverage === false) {
            throw new \RuntimeException("Failed to read input file: $inputFile");
        }

        $filter = $coverage->filter();
        // Use reflection to set the private property $isFileCache on $filter to []
        $reflection = new \ReflectionClass($filter);
        $property = $reflection->getProperty('isFileCache');
        $property->setAccessible(true);
        $property->setValue($filter, []);

        $this->overrideFilesKeys($filter, 'files', $oldPrefix, $newPrefix);

        $data = $coverage->getData();
        $lineCoverage = $data->lineCoverage();
        foreach (array_keys($lineCoverage) as $file) {
            $newFile = str_replace($oldPrefix, $newPrefix, $file);
            $data->renameFile($file, $newFile);        
        }

        // Use reflection to acess the private property $analyser
        $reflection = new \ReflectionClass($coverage);
        $property = $reflection->getProperty('analyser');
        $property->setAccessible(true);
        $analyser = $property->getValue($coverage);
        $this->overrideFilesKeys($analyser, 'classes', $oldPrefix, $newPrefix);
        $this->overrideFilesKeys($analyser, 'traits', $oldPrefix, $newPrefix);
        $this->overrideFilesKeys($analyser, 'functions', $oldPrefix, $newPrefix);
        $this->overrideFilesKeys($analyser, 'ignoredLines', $oldPrefix, $newPrefix);
        $this->overrideFilesKeys($analyser, 'executableLines', $oldPrefix, $newPrefix);
        $this->overrideFilesKeys($analyser, 'linesOfCode', $oldPrefix, $newPrefix);


        // Dump the data to stdout
        $output->writeln("Original data:");
        $output->writeln(print_r($coverage, true));

        return Command::SUCCESS;
    }

    protected function overrideFilesKeys(object $object, string $propertyName, string $oldPrefix, string $newPrefix): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $files = $property->getValue($object);
        $newFiles = [];
        foreach ($files as $file => $value) {
            $newFile = str_replace($oldPrefix, $newPrefix, $file);
            $newFiles[$newFile] = $value;
        }
        $property->setValue($object, $newFiles);
    }


}